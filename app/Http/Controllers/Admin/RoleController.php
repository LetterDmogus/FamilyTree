<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class RoleController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('view_roles');

        $roles = QueryBuilder::for(Role::with('permissions'))
            ->allowedFilters(
                AllowedFilter::partial('name'),
            )
            ->allowedSorts('name', 'created_at')
            ->defaultSort('name')
            ->paginate(request('per_page', 10))
            ->withQueryString();

        return Inertia::render('Admin/Roles/Index', [
            'items' => $roles,
            'allPermissions' => Permission::all(),
            'filters' => request()->all(['search', 'sort', 'per_page']),
            'can' => [
                'create' => auth()->user()->can('create_roles'),
                'update' => auth()->user()->can('update_roles'),
                'delete' => auth()->user()->can('delete_roles'),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create_roles');

        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'nullable|array',
        ]);

        $role = Role::create(['name' => $validated['name'], 'guard_name' => 'web']);
        
        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return back()->with('success', 'Role created successfully.');
    }

    public function update(Request $request, Role $role)
    {
        $this->authorize('update_roles');

        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name,'.$role->id,
            'permissions' => 'nullable|array',
        ]);

        $role->update(['name' => $validated['name']]);
        
        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return back()->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        $this->authorize('delete_roles');

        if ($role->name === 'superadmin') {
            return back()->with('error', 'Cannot delete superadmin role.');
        }

        $role->delete();

        return back()->with('success', 'Role deleted successfully.');
    }
}
