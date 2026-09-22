<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Branch::class);

        $branches = Branch::query()
            ->withCount(['stores', 'users'])
            ->orderBy('name')
            ->paginate(20);

        return view('branches.index', compact('branches'));
    }

    public function create(): View
    {
        $this->authorize('create', Branch::class);

        return view('branches.create');
    }

    public function store(StoreBranchRequest $request): RedirectResponse
    {
        $branch = Branch::create($request->validated());

        return redirect()
            ->route('branches.index')
            ->with('status', "Branch {$branch->name} created.");
    }

    public function edit(Branch $branch): View
    {
        $this->authorize('update', $branch);

        return view('branches.edit', compact('branch'));
    }

    public function update(UpdateBranchRequest $request, Branch $branch): RedirectResponse
    {
        $branch->update($request->validated());

        return redirect()
            ->route('branches.index')
            ->with('status', "Branch {$branch->name} updated.");
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        $this->authorize('delete', $branch);

        // A branch with stores or users attached cannot be removed —
        // those records would be orphaned and the audit trail broken.
        if ($branch->stores()->exists() || $branch->users()->exists()) {
            return back()->withErrors([
                'branch' => 'This branch still has stores or users assigned. Reassign them first.',
            ]);
        }

        $branch->delete();

        return redirect()
            ->route('branches.index')
            ->with('status', "Branch {$branch->name} deleted.");
    }
}