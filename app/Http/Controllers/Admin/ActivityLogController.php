<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ActivityLogController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('manage_settings');

        $logs = QueryBuilder::for(ActivityLog::with(['user', 'subject']))
            ->allowedFilters(
                'action',
                AllowedFilter::partial('description'),
                AllowedFilter::exact('user_id')
            )
            ->defaultSort('-created_at')
            ->paginate(request('per_page', 20))
            ->withQueryString();

        return Inertia::render('Admin/Logs/Index', [
            'items' => $logs,
            'filters' => request()->all(['search', 'action', 'per_page']),
        ]);
    }
}
