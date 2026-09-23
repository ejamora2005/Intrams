<?php

namespace App\Services;

use App\Models\CoordinatorDevice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class CoordinatorDeviceService
{
    public const COOKIE = 'ims_coordinator_device';

    /**
     * Check whether this request comes from the current trusted device.
     * A coordinator without a device is allowed through so it can be registered
     * after Fortify completes password authentication.
     */
    public function isAllowedForLogin(User $coordinator, Request $request): bool
    {
        if ($coordinator->role !== 'coordinator') {
            return true;
        }

        $device = CoordinatorDevice::query()
            ->where('user_id', $coordinator->id)
            ->whereNull('revoked_at')
            ->first();

        if ($device === null) {
            return true;
        }

        $presentedToken = $request->cookie(self::COOKIE);

        return is_string($presentedToken)
            && hash_equals($device->token_hash, hash('sha256', $presentedToken));
    }

    /**
     * Register the first trusted device after Fortify has authenticated the user.
     */
    public function registerAuthenticatedDevice(User $coordinator, Request $request): void
    {
        if ($coordinator->role !== 'coordinator') {
            return;
        }

        DB::transaction(function () use ($coordinator, $request): void {
            $lockedCoordinator = User::query()->lockForUpdate()->findOrFail($coordinator->id);
            $device = CoordinatorDevice::query()->where('user_id', $lockedCoordinator->id)->whereNull('revoked_at')->first();

            if ($device !== null) {
                $device->forceFill(['last_seen_at' => now()])->save();

                return;
            }

            $token = Str::random(80);
            CoordinatorDevice::query()->create([
                'user_id' => $lockedCoordinator->id,
                'token_hash' => hash('sha256', $token),
                'device_label' => Str::limit((string) $request->userAgent(), 255, ''),
                'last_seen_at' => now(),
                'registered_at' => now(),
            ]);

            Cookie::queue(cookie(
                self::COOKIE,
                $token,
                60 * 24 * 90,
                '/',
                null,
                $request->isSecure(),
                true,
                false,
                'lax',
            ));

        });
    }

    public function reset(User $coordinator): void
    {
        DB::transaction(function () use ($coordinator): void {
            CoordinatorDevice::query()
                ->where('user_id', $coordinator->id)
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now(), 'updated_at' => now()]);
        });
    }
}
