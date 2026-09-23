<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Restrict a route to active accounts with one of the supplied roles.
     *
     * @param  array<int, string>  $roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_unless(
            $user !== null
                && $user->status === 'active'
                && in_array($user->role, $roles, true),
            Response::HTTP_FORBIDDEN,
        );

        return $next($request);
    }
}
