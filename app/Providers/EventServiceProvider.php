<?php

namespace App\Providers;

use App\Services\ActivityChanges;
use App\Services\AuditService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        foreach (['created', 'updated', 'deleting', 'deleted', 'restoring', 'restored', 'forceDeleted'] as $event) {
            Event::listen('eloquent.'.$event.': App\\Models\\*', function (string $name, array $payload) use ($event): void {
                app(ActivityChanges::class)->capture($event, $payload[0]);
            });
        }
        Event::listen(Login::class, fn (Login $event) => app(AuditService::class)->record('auth.login', $event->user, actor: $event->user));
        Event::listen(Logout::class, function (Logout $event): void {
            if ($event->user !== null) {
                app(AuditService::class)->record('auth.logout', $event->user, actor: $event->user);
            }
        });
        Event::listen(Failed::class, fn (Failed $event) => app(AuditService::class)->record('auth.failed', newValues: ['email' => $event->credentials['email'] ?? null], outcome: 'denied'));
        Event::listen(Lockout::class, fn (Lockout $event) => app(AuditService::class)->record('auth.lockout', outcome: 'denied'));
        Event::listen(PasswordReset::class, fn (PasswordReset $event) => app(AuditService::class)->record('auth.password_reset', $event->user, actor: $event->user));
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
