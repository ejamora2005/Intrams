<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\AuditService;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class RecordActivity
{
    public function __construct(private readonly AuditService $audit) {}

    public function handle(Request $request, Closure $next): Response
    {
        $started = microtime(true);
        $actor = $request->user();
        $response = null;
        $exception = null;
        $request->attributes->set('activity.capture', true);
        $request->attributes->set('activity.changes', []);
        $this->audit->requestId($request);
        try {
            return $response = $next($request);
        } catch (Throwable $caught) {
            $exception = $caught;
            throw $caught;
        } finally {
            $actor ??= $request->user();
            if ($this->shouldRecord($request, $actor)) {
                $exception ??= $response?->exception ?? null;
                $status = $response?->getStatusCode() ?? match (true) {
                    $exception instanceof ValidationException => 422,
                    $exception instanceof AuthenticationException => 401,
                    $exception instanceof HttpExceptionInterface => $exception->getStatusCode(),
                    default => 500,
                };
                $flashed = $request->hasSession() ? $request->session()->get('_flash.new', []) : [];
                $hasErrors = in_array('errors', $flashed, true) || in_array('error', $flashed, true);
                $outcome = match (true) {
                    in_array($status, [401, 403, 419, 429], true) => 'denied',
                    $actor === null && $request->is('admin*', 'gam*', 'tabulator*', 'coordinator*') => 'denied',
                    $status >= 400 || $hasErrors || $exception !== null => 'failed',
                    default => 'success',
                };
                $this->audit->recordRequest($request, $actor, [
                    'request' => [
                        'method' => $request->method(),
                        'path' => '/'.($request->route()?->uri() ?? $request->path()),
                        'route' => $request->route()?->getName(),
                        'parameters' => collect($request->route()?->parameters() ?? [])->map(fn ($value) => $value instanceof Model ? ['type' => class_basename($value), 'id' => $value->getKey(), 'label' => $value->full_name ?? $value->name ?? $value->label ?? $value->title] : $value)->all(),
                        'status' => $status,
                        'duration_ms' => (int) round((microtime(true) - $started) * 1000),
                        'input' => $request->all(),
                    ],
                    'actor' => $actor?->only(['id', 'name', 'email', 'role']),
                    'changes' => $request->attributes->get('activity.changes', []),
                    'change_state' => $outcome === 'success' ? 'completed' : 'attempted',
                    'validation_fields' => $exception instanceof ValidationException ? array_keys($exception->errors()) : [],
                    'error_type' => $exception !== null ? class_basename($exception) : null,
                ], $outcome);
            }
            $request->attributes->set('activity.capture', false);
        }
    }

    private function shouldRecord(Request $request, ?User $actor): bool
    {
        if ($request->is('up', 'sanctum/csrf-cookie') || $request->isMethod('HEAD')) {
            return false;
        }

        return $actor !== null
            || collect($request->route()?->gatherMiddleware() ?? [])->contains(fn ($middleware) => is_string($middleware) && str_starts_with($middleware, 'auth'))
            || $request->is('admin*', 'gam*', 'tabulator*', 'coordinator*')
            || (! $request->isMethodSafe() && $request->is('login', 'logout', 'register', '*password*', 'two-factor*', 'email/verification*'));
    }
}
