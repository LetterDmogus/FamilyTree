<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UserController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('view_users');

        $canAccessTrash = auth()->user()->can('access_trash_users');
        $requestedTrashed = request()->get('trashed');

        request()->merge([
            'filter' => array_merge(request()->get('filter', []), [
                'search' => request()->get('search'),
                'trashed' => $canAccessTrash ? $requestedTrashed : null,
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
            'roles' => Role::where('name', '!=', 'superadmin')->get(),
            'can' => [
                'create' => auth()->user()->can('create_users'),
                'update' => auth()->user()->can('update_users'),
                'delete' => auth()->user()->can('delete_users'),
                'access_trash' => $canAccessTrash,
            ]
        ]);
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('update_users');

        if ($user->hasRole('superadmin')) {
            abort(403, 'Super Admin tidak dapat dimodifikasi.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'role' => 'required|string|exists:roles,name|not_in:superadmin',
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
        $this->authorize('update_users');

        if ($user->hasRole('superadmin')) {
            abort(403);
        }

        $user->update([
            'password' => bcrypt('password'),
        ]);

        return back()->with('success', 'Password reset to default "password" for '.$user->name);
    }

    public function destroy(User $user)
    {
        $this->authorize('delete_users');

        if ($user->hasRole('superadmin')) {
            abort(403, 'Super Admin tidak dapat dihapus.');
        }

        try {
            $user->delete();
            return back()->with('success', 'User deleted and tree healed successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function restore($id)
    {
        $this->authorize('access_trash_users');

        $user = User::withTrashed()->findOrFail($id);
        
        if ($user->hasRole('superadmin')) {
            abort(403);
        }

        $user->restore();

        return back()->with('success', 'User restored successfully.');
    }

    public function forceDestroy($id)
    {
        $this->authorize('access_trash_users');

        $user = User::withTrashed()->findOrFail($id);

        if ($user->hasRole('superadmin')) {
            abort(403);
        }

        $user->forceDelete();

        return back()->with('success', 'User permanently deleted.');
    }
}
