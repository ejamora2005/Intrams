<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class AuditService
{
    public function __construct(private readonly ActivitySanitizer $sanitizer) {}

    public function requestId(Request $request): string
    {
        if (! $request->attributes->has('activity.request_id')) {
            $request->attributes->set('activity.request_id', (string) Str::uuid());
        }

        return $request->attributes->get('activity.request_id');
    }

    /**
     * Store an append-only record of a major system action.
     *
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function record(string $action, ?Model $auditable = null, ?array $oldValues = null, ?array $newValues = null, string $outcome = 'success', ?User $actor = null): void
    {
        $user = $actor ?? auth()->user();
        $request = request();

        $this->write($action, $auditable, $oldValues, $newValues, $outcome, $user, $request);
    }

    public function recordRequest(Request $request, ?User $actor, array $details, string $outcome): void
    {
        $subject = collect($request->route()?->parameters() ?? [])->first(fn ($value) => $value instanceof Model);
        $action = 'request.'.($request->route()?->getName() ?: strtolower($request->method()).'.'.($request->route()?->uri() ?? 'unknown'));
        $this->write($action, $subject, null, $details, $outcome, $actor, $request);
    }

    private function write(string $action, ?Model $auditable, ?array $oldValues, ?array $newValues, string $outcome, ?User $user, Request $request): void
    {
        $requestId = $this->requestId($request);
        try {
            DB::table('audit_logs')->insert([
                'user_id' => $user?->id,
                'actor_role' => $user?->role,
                'action' => Str::limit($action, 255, ''),
                'auditable_type' => $auditable !== null ? $auditable::class : Request::class,
                'auditable_id' => $auditable?->getKey(),
                'old_values' => $oldValues === null ? null : json_encode($this->sanitizer->sanitize($oldValues), JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE),
                'new_values' => $newValues === null ? null : json_encode($this->sanitizer->sanitize($newValues), JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE),
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit($request->userAgent() ?? '', 2000, ''),
                'request_id' => $requestId,
                'outcome' => $outcome,
                'created_at' => now(),
            ]);
        } catch (Throwable $exception) {
            // An audit storage failure must not break scoring or a roster save.
            // Keep a fallback without SQL, request contents, or credentials.
            Log::error('Activity log could not be stored.', ['action' => $action, 'request_id' => $requestId, 'exception_type' => $exception::class]);
        }
    }
}
