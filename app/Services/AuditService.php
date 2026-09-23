<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuditService
{
    /**
     * Store an append-only record of a major system action.
     *
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function record(string $action, Model $auditable, ?array $oldValues = null, ?array $newValues = null, string $outcome = 'success'): void
    {
        $user = auth()->user();
        $request = request();

        DB::table('audit_logs')->insert([
            'user_id' => $user?->id,
            'actor_role' => $user?->role,
            'action' => $action,
            'auditable_type' => $auditable::class,
            'auditable_id' => $auditable->getKey(),
            'old_values' => $oldValues === null ? null : json_encode($oldValues, JSON_THROW_ON_ERROR),
            'new_values' => $newValues === null ? null : json_encode($newValues, JSON_THROW_ON_ERROR),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'request_id' => (string) Str::uuid(),
            'outcome' => $outcome,
            'created_at' => now(),
        ]);
    }
}
