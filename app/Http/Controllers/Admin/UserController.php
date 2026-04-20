<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UserController extends Controller
{
    public function index()
    {
        request()->merge([
            'filter' => array_merge(request()->get('filter', []), [
                'search' => request()->get('search'),
                'trashed' => request()->get('trashed'),
            ]),
        ]);

        $users = QueryBuilder::for(User::with(['roles', 'profile']))
            ->allowedFilters(
                AllowedFilter::scope('search'),
                AllowedFilter::trashed(),
            )
            ->allowedSorts('name', 'email', 'created_at')
            ->defaultSort('-created_at')
            ->paginate(request('per_page', 10))
            ->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'items' => $users,
            'filters' => request()->all(['search', 'trashed', 'sort', 'per_page']),
            'roles' => Role::all(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'role' => 'required|string|exists:roles,name',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        $user->syncRoles([$validated['role']]);

        return back()->with('success', 'User updated successfully.');
    }

    public function resetPassword(User $user)
    {
        $user->update([
            'password' => bcrypt('password'),
        ]);

        return back()->with('success', 'Password reset to default "password" for '.$user->name);
    }

    public function destroy(User $user)
    {
        $user->delete();

        return back()->with('success', 'User soft deleted successfully.');
    }

    public function restore($id)
    {
        User::withTrashed()->findOrFail($id)->restore();

        return back()->with('success', 'User restored successfully.');
    }

    public function forceDestroy($id)
    {
        if (! auth()->user()->hasRole('superadmin')) {
            abort(403);
        }

        User::withTrashed()->findOrFail($id)->forceDelete();

        return back()->with('success', 'User permanently deleted.');
    }
}
