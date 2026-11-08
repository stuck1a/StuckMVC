<?php
/*
 *  This file is part of StuckMVC <https://stuck1a.de/coding/stuckmvc>,
 *  Copyright (c) 2021-2026.
 *  The StuckMVC framework is free software: you can redistribute it and/or modify it
 *  under the terms of the GNU General Public License version 3 as published by
 *  the Free Software Foundation. See the GNU General Public License for more details.
 *
 *  FOR A SOMEWHAT FREER WORLD.
 */

declare(strict_types=1);

namespace Stuck1A\StuckMVC\Core;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionNamedType;

/**
 * Minimal PSR-11 dependency injection container, which replaces the former singleton pattern.
 *
 * Services are either registered explicitly by a factory or instance, or resolved automatically ("autowiring"):
 * any instantiable class whose constructor parameters are type-hinted with resolvable services (or have default
 * values) can be created without registration. Every service is shared, so it's created once per request.
 */
final class Container implements ContainerInterface
{
    /** @var array<string, callable(self): mixed> */
    private array $factories = [];

    /** @var array<string, mixed> */
    private array $instances = [];

    /** @var array<string, true> */
    private array $resolving = [];

    /**
     * Registers a factory which creates the service on first request.
     *
     * @param callable(self): mixed $factory
     */
    public function set(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
        unset($this->instances[$id]);
    }

    /**
     * Registers an already existing service instance.
     */
    public function instance(string $id, mixed $service): void
    {
        $this->instances[$id] = $service;
    }

    /**
     * Whether a service was registered explicitly (unlike has(), which also covers autowirable classes).
     */
    public function bound(string $id): bool
    {
        return array_key_exists($id, $this->instances) || isset($this->factories[$id]);
    }

    public function has(string $id): bool
    {
        return isset($this->instances[$id]) || isset($this->factories[$id]) || class_exists($id);
    }

    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }
        if (isset($this->resolving[$id])) {
            throw new class ("Circular dependency while resolving '{$id}'.") extends \RuntimeException implements ContainerExceptionInterface {};
        }
        $this->resolving[$id] = true;
        try {
            $service = isset($this->factories[$id]) ? ($this->factories[$id])($this) : $this->make($id);
        } finally {
            unset($this->resolving[$id]);
        }
        return $this->instances[$id] = $service;
    }

    /**
     * Creates a new (not shared) instance of a class by autowiring its constructor.
     *
     * @template T of object
     *
     * @param class-string<T>      $class
     * @param array<string, mixed> $parameters Explicit constructor arguments by parameter name.
     *
     * @return T
     */
    public function make(string $class, array $parameters = []): object
    {
        if (!class_exists($class)) {
            throw new class ("Service '{$class}' not found.") extends \RuntimeException implements NotFoundExceptionInterface {};
        }
        $reflection = new ReflectionClass($class);
        if (!$reflection->isInstantiable()) {
            throw new class ("Class '{$class}' is not instantiable.") extends \RuntimeException implements ContainerExceptionInterface {};
        }
        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            return $reflection->newInstance();
        }
        $arguments = [];
        foreach ($constructor->getParameters() as $parameter) {
            $name = $parameter->getName();
            $type = $parameter->getType();
            if (array_key_exists($name, $parameters)) {
                $arguments[] = $parameters[$name];
            } elseif ($type instanceof ReflectionNamedType && !$type->isBuiltin() && $this->has($type->getName())) {
                $arguments[] = $this->get($type->getName());
            } elseif ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
            } elseif ($type !== null && $type->allowsNull()) {
                $arguments[] = null;
            } else {
                throw new class ("Cannot resolve parameter \${$name} of {$class}.") extends \RuntimeException implements ContainerExceptionInterface {};
            }
        }
        return $reflection->newInstanceArgs($arguments);
    }
}
