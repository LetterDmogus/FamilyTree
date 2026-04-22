<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MasterAdditionalField;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class DataDetailController extends Controller
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

        $items = QueryBuilder::for(MasterAdditionalField::class)
            ->allowedFilters(
                AllowedFilter::partial('name'),
                AllowedFilter::exact('group_name'),
                AllowedFilter::trashed(),
            )
            ->allowedSorts('name', 'group_name', 'created_at')
            ->defaultSort('group_name', 'name')
            ->paginate(request('per_page', 10))
            ->withQueryString();

        return Inertia::render('Admin/DataDetails/Index', [
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
            'group_name' => 'required|string|max:255',
            'icon_key' => 'required|string|max:255',
            'input_type' => 'required|in:text,textarea,date,select',
            'options' => 'nullable|array',
        ]);

        MasterAdditionalField::create($validated);

        return back()->with('success', 'Data field created successfully.');
    }

    public function update(Request $request, MasterAdditionalField $dataDetail)
    {
        $this->authorize('update_master');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'group_name' => 'required|string|max:255',
            'icon_key' => 'required|string|max:255',
            'input_type' => 'required|in:text,textarea,date,select',
            'options' => 'nullable|array',
        ]);

        $dataDetail->update($validated);

        return back()->with('success', 'Data field updated successfully.');
    }

    public function destroy(MasterAdditionalField $dataDetail)
    {
        $this->authorize('delete_master');

        $dataDetail->delete();

        return back()->with('success', 'Data field soft deleted.');
    }

    public function restore($id)
    {
        $this->authorize('access_trash_master');

        MasterAdditionalField::withTrashed()->findOrFail($id)->restore();

        return back()->with('success', 'Data field restored.');
    }

    public function forceDestroy($id)
    {
        $this->authorize('access_trash_master');

        MasterAdditionalField::withTrashed()->findOrFail($id)->forceDelete();

        return back()->with('success', 'Data field permanently deleted.');
    }
}
