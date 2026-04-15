<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Inertia\Inertia;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UserController extends Controller
{
    public function index()
    {
        $users = QueryBuilder::for(User::class)
            ->allowedFilters(
                'name',
                'email',
                AllowedFilter::trashed(),
            )
            ->allowedSorts('name', 'email', 'created_at')
            ->defaultSort('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'items' => $users,
            'filters' => request()->all(['search', 'trashed', 'sort']),
        ]);
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
