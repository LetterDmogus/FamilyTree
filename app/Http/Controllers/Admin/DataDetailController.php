<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MasterAdditionalField;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class DataDetailController extends Controller
{
    public function index()
    {
        request()->merge([
            'filter' => array_merge(request()->get('filter', []), [
                'search' => request()->get('search'),
                'trashed' => request()->get('trashed'),
            ]),
        ]);

        $items = QueryBuilder::for(MasterAdditionalField::class)
            ->allowedFilters(
                AllowedFilter::scope('search'),
                AllowedFilter::trashed(),
            )
            ->allowedSorts('name', 'created_at')
            ->defaultSort('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/DataDetails/Index', [
            'items' => $items,
            'filters' => request()->all(['search', 'trashed', 'sort']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon_key' => 'nullable|string|max:255',
            'input_type' => 'required|in:text,textarea,date,select',
            'options' => 'nullable|array',
        ]);

        MasterAdditionalField::create($validated);

        return back()->with('success', 'Data detail field created.');
    }

    public function update(Request $request, MasterAdditionalField $dataDetail)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon_key' => 'nullable|string|max:255',
            'input_type' => 'required|in:text,textarea,date,select',
            'options' => 'nullable|array',
        ]);

        $dataDetail->update($validated);

        return back()->with('success', 'Data detail field updated.');
    }

    public function destroy(MasterAdditionalField $dataDetail)
    {
        $dataDetail->delete();

        return back()->with('success', 'Data detail field soft deleted.');
    }

    public function restore($id)
    {
        MasterAdditionalField::withTrashed()->findOrFail($id)->restore();

        return back()->with('success', 'Data detail field restored.');
    }

    public function forceDestroy($id)
    {
        if (! auth()->user()->hasRole('superadmin')) {
            abort(403);
        }

        MasterAdditionalField::withTrashed()->findOrFail($id)->forceDelete();

        return back()->with('success', 'Data detail field permanently deleted.');
    }
}
