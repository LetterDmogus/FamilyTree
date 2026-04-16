<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class RoleController extends Controller
{
    public function index()
    {
        request()->merge([
            'filter' => array_merge(request()->get('filter', []), [
                'search' => request()->get('search'),
                'trashed' => request()->get('trashed'),
            ]),
        ]);

        $roles = QueryBuilder::for(Role::with('permissions'))
            ->allowedFilters(
                AllowedFilter::scope('search'),
                AllowedFilter::trashed(),
            )
            ->allowedSorts('name', 'created_at')
            ->defaultSort('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Roles/Index', [
            'items' => $roles,
            'filters' => request()->all(['search', 'trashed', 'sort']),
            'allPermissions' => Permission::all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'array',
        ]);

        $role = Role::create(['name' => $validated['name']]);
        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return back()->with('success', 'Role created successfully.');
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name,'.$role->id,
            'permissions' => 'array',
        ]);

        $role->update(['name' => $validated['name']]);
        $role->syncPermissions($validated['permissions'] ?? []);

        return back()->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        $role->delete();

        return back()->with('success', 'Role soft deleted successfully.');
    }

    public function restore($id)
    {
        Role::withTrashed()->findOrFail($id)->restore();

        return back()->with('success', 'Role restored successfully.');
    }

    public function forceDestroy($id)
    {
        if (! auth()->user()->hasRole('superadmin')) {
            abort(403);
        }

        Role::withTrashed()->findOrFail($id)->forceDelete();

        return back()->with('success', 'Role permanently deleted.');
    }
}
