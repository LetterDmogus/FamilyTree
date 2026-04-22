<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MasterSocialMedia;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class SocialMediaController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('view_master');

        $canAccessTrash = auth()->user()->can('access_trash_master');
        $requestedTrashed = request()->get('trashed');

        request()->merge([
            'filter' => array_merge(request()->get('filter', []), [
                'search' => request()->get('search'),
                'trashed' => $canAccessTrash ? $requestedTrashed : null,
            ]),
        ]);

        $items = QueryBuilder::for(MasterSocialMedia::class)
            ->allowedFilters(
                AllowedFilter::partial('name'),
                AllowedFilter::trashed(),
            )
            ->allowedSorts('name', 'created_at')
            ->defaultSort('name')
            ->paginate(request('per_page', 10))
            ->withQueryString();

        return Inertia::render('Admin/SocialMedias/Index', [
            'items' => $items,
            'filters' => request()->all(['search', 'trashed', 'sort', 'per_page']),
            'can' => [
                'create' => auth()->user()->can('create_master'),
                'update' => auth()->user()->can('update_master'),
                'delete' => auth()->user()->can('delete_master'),
                'access_trash' => $canAccessTrash,
            ]
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create_master');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'prefix' => 'required|string|max:255',
            'icon_url' => 'nullable|string|max:2048',
        ]);

        MasterSocialMedia::create($validated);

        return back()->with('success', 'Social media platform created.');
    }

    public function update(Request $request, MasterSocialMedia $socialMedia)
    {
        $this->authorize('update_master');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'prefix' => 'required|string|max:255',
            'icon_url' => 'nullable|string|max:2048',
        ]);

        $socialMedia->update($validated);

        return back()->with('success', 'Social media platform updated.');
    }

    public function destroy(MasterSocialMedia $socialMedia)
    {
        $this->authorize('delete_master');

        $socialMedia->delete();

        return back()->with('success', 'Social media platform soft deleted.');
    }

    public function restore($id)
    {
        $this->authorize('access_trash_master');

        MasterSocialMedia::withTrashed()->findOrFail($id)->restore();

        return back()->with('success', 'Social media platform restored.');
    }

    public function forceDestroy($id)
    {
        $this->authorize('access_trash_master');

        MasterSocialMedia::withTrashed()->findOrFail($id)->forceDelete();

        return back()->with('success', 'Social media platform permanently deleted.');
    }
}
