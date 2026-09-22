<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Product::class);

        $search = trim((string) $request->input('search', ''));
        $products = $this->filteredProducts($search);

        return view('products.index', compact('products', 'search'));
    }

    /**
     * Small JSON endpoint for live search. Returns just what the table needs
     * to re-render — no layout, no nav, no wasted bytes.
     */
    public function search(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        $search = trim((string) $request->input('search', ''));
        $products = $this->filteredProducts($search);

        return response()->json([
            'count' => $products->total(),
            'rows' => $products->map(fn ($p) => [
                'id' => $p->id,
                'sku' => $p->sku,
                'name' => $p->name,
                'unit' => $p->unit,
                'cost_price' => (float) $p->cost_price,
                'selling_price' => (float) $p->selling_price,
                'reorder_level' => (int) $p->reorder_level,
                'is_active' => (bool) $p->is_active,
                'edit_url' => route('products.edit', $p),
                'delete_url' => route('products.destroy', $p),
                'can_edit' => $request->user()->can('update', $p),
                'can_delete' => $request->user()->can('delete', $p),
            ]),
        ]);
    }

    private function filteredProducts(string $search): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Product::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();
    }

    public function create(): View
    {
        $this->authorize('create', Product::class);

        return view('products.create');
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = Product::create($request->validated());

        return redirect()
            ->route('products.index')
            ->with('status', "Product {$product->name} created.");
    }

    public function show(Product $product): View
    {
        $this->authorize('view', $product);

        $product->loadCount('stockLevels');

        return view('products.show', compact('product'));
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        return view('products.edit', compact('product'));
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        return redirect()
            ->route('products.index')
            ->with('status', "Product {$product->name} updated.");
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        // A product with stock on shelves, or movement history, cannot be
        // removed — either would break the ledger's referential integrity.
        $hasStock = $product->stockLevels()->where('quantity', '>', 0)->exists();
        $hasMovements = $product->stockMovements()->exists();

        if ($hasStock || $hasMovements) {
            return back()->withErrors([
                'product' => 'This product has stock or movement history. Deactivate it instead of deleting.',
            ]);
        }

        $product->delete();

        return redirect()
            ->route('products.index')
            ->with('status', "Product {$product->name} deleted.");
    }
}