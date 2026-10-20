<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\EditionSport;
use App\Models\User;
use App\Services\ActivityJournal;
use App\Services\ActivitySanitizer;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SystemLogController extends Controller
{
    public function index(Request $request, ActivityJournal $journal): View
    {
        $roles = ['admin' => 'Administrator', 'gam' => 'General Athletics Manager (GAM)', 'tabulator' => 'Tabulator', 'coordinator' => 'Coordinator'];
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'actor_id' => ['nullable', 'integer'],
            'role' => ['nullable', Rule::in(array_keys($roles))],
            'action' => ['nullable', 'string', 'max:150'],
            'activity' => ['nullable', Rule::in(array_keys(ActivityJournal::TYPES))],
            'outcome' => ['nullable', Rule::in(['success', 'denied', 'failed'])],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'request_id' => ['nullable', 'uuid'],
        ]);
        $timezone = 'Asia/Manila';
        $query = $journal->query()
            ->with('user:id,name,email')
            ->when($request->filled('actor_id'), fn ($query) => $query->where('user_id', $request->integer('actor_id')))
            ->when($request->filled('role'), fn ($query) => $query->where('actor_role', $request->string('role')->value()))
            ->when($request->filled('action'), fn ($query) => $query->where('action', 'like', '%'.$request->string('action')->value().'%'))
            ->when($request->filled('outcome'), fn ($query) => $query->where('outcome', $request->string('outcome')->value()))
            ->when($request->filled('request_id'), fn ($query) => $query->where('request_id', $filters['request_id']))
            ->when($request->filled('from'), fn ($query) => $query->where('created_at', '>=', CarbonImmutable::parse($filters['from'], $timezone)->startOfDay()->utc()))
            ->when($request->filled('to'), fn ($query) => $query->where('created_at', '<=', CarbonImmutable::parse($filters['to'], $timezone)->endOfDay()->utc()))
            ->when($request->filled('search'), fn ($query) => $query->where(function ($inner) use ($filters): void {
                $search = '%'.$filters['search'].'%';
                $inner->where('action', 'like', $search)->orWhere('auditable_type', 'like', $search)
                    ->orWhere('request_id', 'like', $search)->orWhere('ip_address', 'like', $search)
                    ->orWhere('new_values', 'like', $search)->orWhere('old_values', 'like', $search)
                    ->orWhereHas('user', fn ($users) => $users->where('name', 'like', $search)->orWhere('email', 'like', $search));
                $eventIds = EditionSport::query()->whereHas('sport', fn ($sports) => $sports->withTrashed()->where('name', 'like', $search))->pluck('id');
                if ($eventIds->isNotEmpty()) {
                    $inner->orWhereIn('new_values->request->input->edition_sport_id', $eventIds);
                }
            }));
        if ($request->filled('activity')) {
            $journal->filter($query, $filters['activity']);
        }
        $summary = (clone $query)->reorder()->selectRaw('COUNT(*) AS total, SUM(outcome = ?) AS successful, SUM(outcome IN (?, ?)) AS issues', ['success', 'failed', 'denied'])->first();
        $logs = $query
            ->latest('created_at')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();
        $logs->getCollection()->each(function (AuditLog $log): void {
            $log->old_values = app(ActivitySanitizer::class)->sanitize($log->old_values);
            $log->new_values = app(ActivitySanitizer::class)->sanitize($log->new_values);
        });
        $presentations = $journal->presentations($logs->getCollection());
        $logs->getCollection()->each(fn (AuditLog $log, $index) => $log->setAttribute('presentation', $presentations[$index]));

        return view('admin.system-logs.index', [
            'logs' => $logs,
            'actors' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
            'filters' => $filters,
            'roles' => $roles,
            'summary' => $summary,
            'timezone' => $timezone,
            'activityTypes' => ActivityJournal::TYPES,
        ]);
    }
}
