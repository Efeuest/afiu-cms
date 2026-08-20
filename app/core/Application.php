<?php

declare(strict_types=1);

namespace AfiuCMS\Core;

use AfiuCMS\Core\Contracts\Middleware;
use AfiuCMS\Core\Http\Request;
use AfiuCMS\Core\Http\Response;
use AfiuCMS\Core\Routing\Router;
use LogicException;

final class Application
{
    private array $middleware = [];

    public function __construct(
        private readonly Container $container,
        private readonly Router $router
    ) {}

    public function middleware(string $middleware): void
    {
        $this->middleware[] = $middleware;
    }

    public function run(): void
    {
        $request = Request::capture();
        $destination = fn (Request $req): Response => $this->router->dispatch($req);
        $pipeline = array_reduce(
            array_reverse($this->middleware),
            fn (callable $next, string $middleware): callable => function (Request $req) use ($middleware, $next): Response {
                $instance = $this->container->make($middleware);
                if (!$instance instanceof Middleware) {
                    throw new LogicException("{$middleware} must implement Middleware.");
                }
                return $instance->handle($req, $next);
            },
            $destination
        );
        $pipeline($request)->send();
    }
}
