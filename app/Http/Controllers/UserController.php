<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Branch;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->with(['branch:id,name', 'store:id,name'])
            ->orderBy('name')
            ->paginate(20);

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        $branches = Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $stores = Store::where('is_active', true)->orderBy('name')->get(['id', 'name', 'branch_id']);

        return view('users.create', compact('branches', 'stores'));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        return redirect()
            ->route('users.index')
            ->with('status', "User {$user->name} created.");
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        $branches = Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $stores = Store::where('is_active', true)->orderBy('name')->get(['id', 'name', 'branch_id']);

        return view('users.edit', compact('user', 'branches', 'stores'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        // Only touch the password if the admin typed a new one.
        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()
            ->route('users.index')
            ->with('status', "User {$user->name} updated.");
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        // Users have sales attributed to them. Soft-delete keeps the
        // attribution intact while removing them from active lists.
        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('status', "User {$user->name} removed.");
    }
}