<?php

declare(strict_types=1);

namespace AfiuCMS\Core\Routing;

use AfiuCMS\Core\Container;
use AfiuCMS\Core\Contracts\Middleware;
use AfiuCMS\Core\Http\Request;
use AfiuCMS\Core\Http\Response;
use Closure;
use LogicException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionFunction;

final class Router
{
    private array $routes = [];

    public function __construct(private readonly Container $container) {}

    public function get(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function add(string $method, string $path, callable|array $handler, array $middleware = []): void
    {
        $path = $this->normalize($path);
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'pattern' => $this->compile($path),
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(Request $request): Response
    {
        $allowed = [];
        foreach ($this->routes as $route) {
            if (!preg_match($route['pattern'], $request->path(), $matches)) {
                continue;
            }
            $allowed[] = $route['method'];
            if ($route['method'] !== $request->method()) {
                continue;
            }

            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = rawurldecode((string) $value);
                }
            }
            $request = $request->withRouteParams($params);
            $destination = fn (Request $req): Response => $this->invoke($route['handler'], $req, $params);
            $pipeline = array_reduce(
                array_reverse($route['middleware']),
                fn (callable $next, string $middleware): callable => function (Request $req) use ($middleware, $next): Response {
                    $instance = $this->container->make($middleware);
                    if (!$instance instanceof Middleware) {
                        throw new LogicException("{$middleware} must implement Middleware.");
                    }
                    return $instance->handle($req, $next);
                },
                $destination
            );
            return $pipeline($request);
        }

        if ($allowed !== []) {
            return Response::html('<h1>405 Method Not Allowed</h1>', 405)
                ->withHeader('Allow', implode(', ', array_unique($allowed)));
        }
        return Response::html('<h1>404 Not Found</h1>', 404);
    }

    private function invoke(callable|array $handler, Request $request, array $params): Response
    {
        if (is_array($handler) && is_string($handler[0] ?? null)) {
            $controller = $this->container->make($handler[0]);
            $method = (string) $handler[1];
            $reflection = new ReflectionMethod($controller, $method);
            $arguments = $this->arguments($reflection->getParameters(), $request, $params);
            $result = $controller->{$method}(...$arguments);
        } elseif ($handler instanceof Closure) {
            $reflection = new ReflectionFunction($handler);
            $arguments = $this->arguments($reflection->getParameters(), $request, $params);
            $result = $handler(...$arguments);
        } else {
            $result = $handler($request);
        }

        if (!$result instanceof Response) {
            throw new LogicException('Route handlers must return a Response.');
        }
        return $result;
    }

    private function arguments(array $parameters, Request $request, array $params): array
    {
        $args = [];
        foreach ($parameters as $parameter) {
            $type = $parameter->getType();
            if ($type instanceof ReflectionNamedType && $type->getName() === Request::class) {
                $args[] = $request;
            } elseif (array_key_exists($parameter->getName(), $params)) {
                $args[] = $params[$parameter->getName()];
            } elseif ($parameter->isDefaultValueAvailable()) {
                $args[] = $parameter->getDefaultValue();
            } else {
                throw new LogicException('Cannot resolve route argument $' . $parameter->getName());
            }
        }
        return $args;
    }

    private function normalize(string $path): string
    {
        return $path === '/' ? '/' : '/' . trim($path, '/');
    }

    private function compile(string $path): string
    {
        if ($path === '/') {
            return '#^/$#';
        }
        $segments = explode('/', trim($path, '/'));
        $compiled = [];
        foreach ($segments as $segment) {
            if (preg_match('/^\{([A-Za-z_][A-Za-z0-9_]*)\*\}$/', $segment, $m)) {
                $compiled[] = '(?P<' . $m[1] . '>.+)';
            } elseif (preg_match('/^\{([A-Za-z_][A-Za-z0-9_]*)\}$/', $segment, $m)) {
                $compiled[] = '(?P<' . $m[1] . '>[^/]+)';
            } else {
                $compiled[] = preg_quote($segment, '#');
            }
        }
        return '#^/' . implode('/', $compiled) . '$#';
    }
}
