<?php

declare(strict_types=1);

namespace AfiuCMS\Core;

use Closure;
use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

final class Container
{
    private array $factories = [];
    private array $instances = [];

    public function singleton(string $id, Closure $factory): void
    {
        $this->factories[$id] = ['factory' => $factory, 'singleton' => true];
    }

    public function bind(string $id, Closure $factory): void
    {
        $this->factories[$id] = ['factory' => $factory, 'singleton' => false];
    }

    public function instance(string $id, object $instance): void
    {
        $this->instances[$id] = $instance;
    }

    public function make(string $id): object
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (isset($this->factories[$id])) {
            $definition = $this->factories[$id];
            $object = $definition['factory']($this);
            if ($definition['singleton']) {
                $this->instances[$id] = $object;
            }
            return $object;
        }

        if (!class_exists($id)) {
            throw new RuntimeException("Container cannot resolve {$id}.");
        }

        $reflection = new ReflectionClass($id);
        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            return new $id();
        }

        $arguments = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $arguments[] = $this->make($type->getName());
                continue;
            }
            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }
            throw new RuntimeException("Cannot autowire {$id}::\${$parameter->getName()}.");
        }

        return $reflection->newInstanceArgs($arguments);
    }
}
