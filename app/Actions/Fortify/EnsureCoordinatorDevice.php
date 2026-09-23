<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Services\CoordinatorDeviceService;
use Closure;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;

class EnsureCoordinatorDevice
{
    public function __construct(
        private readonly CoordinatorDeviceService $deviceService,
        private readonly StatefulGuard $guard,
    ) {
    }

    public function handle($request, Closure $next)
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user instanceof User && ! $this->deviceService->isAllowedForLogin($user, $request)) {
            $this->guard->logout();

            throw ValidationException::withMessages([
                Fortify::username() => [trans('auth.failed')],
            ]);
        }

        if ($user instanceof User) {
            $this->deviceService->registerAuthenticatedDevice($user, $request);
        }

        return $next($request);
    }
}
