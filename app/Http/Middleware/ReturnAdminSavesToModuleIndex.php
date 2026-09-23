<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReturnAdminSavesToModuleIndex
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->isMethodSafe() && $response instanceof RedirectResponse && ! $request->session()->has('errors')) {
            $route = $request->route()?->getName() ?? '';
            $destination = match (true) {
                str_starts_with($route, 'admin.editions.') => 'admin.editions.index',
                str_starts_with($route, 'admin.students.') => 'admin.students.index',
                str_starts_with($route, 'admin.teams.') => 'admin.teams.index',
                str_starts_with($route, 'admin.sports.') => 'admin.sports.index',
                str_starts_with($route, 'admin.coordinators.') => 'admin.coordinators.index',
                str_starts_with($route, 'admin.courses.') => 'admin.students.index',
                str_starts_with($route, 'admin.competition.') => 'admin.competition.index',
                str_starts_with($route, 'admin.registrations.') => 'admin.registrations.index',
                str_starts_with($route, 'admin.participation-rules.') => 'admin.participation-rules.index',
                default => null,
            };

            if ($destination !== null && $request->boolean('return_to_module_index')) {
                $parameters = $destination === 'admin.sports.index' && $request->filled('edition_id')
                    ? ['edition_id' => $request->integer('edition_id')]
                    : [];

                $response->setTargetUrl(route($destination, $parameters));
            } elseif ($destination !== null && ($currentPosition = $this->currentAdminPosition($request)) !== null) {
                $response->setTargetUrl($currentPosition);
            }
        }

        return $response;
    }

    private function currentAdminPosition(Request $request): ?string
    {
        $referer = $request->headers->get('referer');
        if (! is_string($referer) || $referer === '') {
            return null;
        }

        $parts = parse_url($referer);
        $basePath = rtrim($request->getBaseUrl(), '/');
        $adminPath = ($basePath === '' ? '' : $basePath).'/admin';

        $path = $parts['path'] ?? '';
        if (($parts['host'] ?? null) !== $request->getHost() || ! ($path === $adminPath || str_starts_with($path, $adminPath.'/'))) {
            return null;
        }

        return $referer;
    }
}
