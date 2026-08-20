<?php

declare(strict_types=1);

namespace AfiuCMS\Core\Middleware;

use AfiuCMS\Core\Config;
use AfiuCMS\Core\Contracts\Middleware;
use AfiuCMS\Core\Http\Request;
use AfiuCMS\Core\Http\Response;

final class EnsureInstalled implements Middleware
{
    public function __construct(private readonly Config $config) {}

    public function handle(Request $request, callable $next): Response
    {
        $installed = (bool) $this->config->get('app.installed', false);
        $isInstall = $request->path() === '/install';
        $isHealth = $request->path() === '/health';
        if (!$installed && !$isInstall && !$isHealth) {
            return Response::redirect('/install');
        }
        if ($installed && $isInstall) {
            return Response::redirect('/admin');
        }
        return $next($request);
    }
}
