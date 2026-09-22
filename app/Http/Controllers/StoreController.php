<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStoreRequest;
use App\Http\Requests\UpdateStoreRequest;
use App\Models\Branch;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StoreController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Store::class);

        $user = $request->user();

        $query = Store::query()
            ->with('branch:id,name')
            ->withCount(['stockLevels', 'users'])
            ->orderBy('name');

        if ($user->isBranchManager()) {
            $query->where('branch_id', $user->branch_id);
        }

        $stores = $query->paginate(20);

        return view('stores.index', compact('stores'));
    }

    public function create(): View
    {
        $this->authorize('create', Store::class);

        $user = request()->user();

        $branches = $user->isAdmin()
            ? Branch::where('is_active', true)->orderBy('name')->get()
            : Branch::where('id', $user->branch_id)->get();

        return view('stores.create', compact('branches'));
    }

    public function store(StoreStoreRequest $request): RedirectResponse
    {
        $store = Store::create($request->validated());

        return redirect()
            ->route('stores.index')
            ->with('status', "Store {$store->name} created.");
    }

    public function show(Store $store): View
    {
        $this->authorize('view', $store);

        $store->load(['branch:id,name']);
        $store->loadCount(['stockLevels', 'users', 'sales']);

        return view('stores.show', compact('store'));
    }

    public function edit(Store $store): View
    {
        $this->authorize('update', $store);

        $user = request()->user();

        $branches = $user->isAdmin()
            ? Branch::where('is_active', true)->orderBy('name')->get()
            : Branch::where('id', $user->branch_id)->get();

        return view('stores.edit', compact('store', 'branches'));
    }

    public function update(UpdateStoreRequest $request, Store $store): RedirectResponse
    {
        $store->update($request->validated());

        return redirect()
            ->route('stores.index')
            ->with('status', "Store {$store->name} updated.");
    }

    public function destroy(Store $store): RedirectResponse
    {
        $this->authorize('delete', $store);

        // A store with stock, sales, or users cannot be removed without
        // orphaning records that the audit trail depends on.
        $hasDependents = $store->stockLevels()->where('quantity', '>', 0)->exists()
            || $store->sales()->exists()
            || $store->users()->exists();

        if ($hasDependents) {
            return back()->withErrors([
                'store' => 'This store still has stock, sales, or users assigned. Clear those first.',
            ]);
        }

        $store->delete();

        return redirect()
            ->route('stores.index')
            ->with('status', "Store {$store->name} deleted.");
    }
}