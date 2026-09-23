<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SystemLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->only(['actor_id', 'role', 'action', 'outcome', 'from', 'to']);
        $logs = AuditLog::query()
            ->with('user:id,name,email')
            ->when($request->filled('actor_id'), fn ($query) => $query->where('user_id', $request->integer('actor_id')))
            ->when($request->filled('role'), fn ($query) => $query->where('actor_role', $request->string('role')->value()))
            ->when($request->filled('action'), fn ($query) => $query->where('action', 'like', '%'.$request->string('action')->value().'%'))
            ->when($request->filled('outcome'), fn ($query) => $query->where('outcome', $request->string('outcome')->value()))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date('to')))
            ->latest('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.system-logs.index', [
            'logs' => $logs,
            'actors' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
            'filters' => $filters,
        ]);
    }
}
