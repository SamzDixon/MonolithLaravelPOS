<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Supplier::class);

        $suppliers = Supplier::query()
            ->withCount('stockReceipts')
            ->orderBy('name')
            ->paginate(20);

        return view('suppliers.index', compact('suppliers'));
    }

    public function create(): View
    {
        $this->authorize('create', Supplier::class);

        return view('suppliers.create');
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        $supplier = Supplier::create($request->validated());

        return redirect()
            ->route('suppliers.index')
            ->with('status', "Supplier {$supplier->name} created.");
    }

    public function edit(Supplier $supplier): View
    {
        $this->authorize('update', $supplier);

        return view('suppliers.edit', compact('supplier'));
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($request->validated());

        return redirect()
            ->route('suppliers.index')
            ->with('status', "Supplier {$supplier->name} updated.");
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $this->authorize('delete', $supplier);

        if ($supplier->stockReceipts()->exists()) {
            return back()->withErrors([
                'supplier' => 'This supplier has receipts on file. Deactivate them instead of deleting.',
            ]);
        }

        $supplier->delete();

        return redirect()
            ->route('suppliers.index')
            ->with('status', "Supplier {$supplier->name} deleted.");
    }
}