<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MasterSocialMedia;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class SocialMediaController extends Controller
{
    public function index()
    {
        request()->merge([
            'filter' => array_merge(request()->get('filter', []), [
                'search' => request()->get('search'),
                'trashed' => request()->get('trashed'),
            ]),
        ]);

        $items = QueryBuilder::for(MasterSocialMedia::class)
            ->allowedFilters(
                AllowedFilter::scope('search'),
                AllowedFilter::trashed(),
            )
            ->allowedSorts('name', 'created_at')
            ->defaultSort('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/SocialMedias/Index', [
            'items' => $items,
            'filters' => request()->all(['search', 'trashed', 'sort']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'prefix' => 'nullable|string|max:255',
            'icon_url' => 'nullable|string|max:255',
        ]);

        MasterSocialMedia::create($validated);

        return back()->with('success', 'Social media created successfully.');
    }

    public function update(Request $request, MasterSocialMedia $socialMedia)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'prefix' => 'nullable|string|max:255',
            'icon_url' => 'nullable|string|max:255',
        ]);

        $socialMedia->update($validated);

        return back()->with('success', 'Social media updated successfully.');
    }

    public function destroy(MasterSocialMedia $socialMedia)
    {
        $socialMedia->delete();

        return back()->with('success', 'Social media soft deleted.');
    }

    public function restore($id)
    {
        MasterSocialMedia::withTrashed()->findOrFail($id)->restore();

        return back()->with('success', 'Social media restored.');
    }

    public function forceDestroy($id)
    {
        if (! auth()->user()->hasRole('superadmin')) {
            abort(403);
        }

        MasterSocialMedia::withTrashed()->findOrFail($id)->forceDelete();

        return back()->with('success', 'Social media permanently deleted.');
    }
}
