<?php

declare(strict_types=1);

namespace Stuck1A\StuckMVC\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Stuck1A\StuckMVC\Core\Container;

final class ContainerTest extends TestCase
{
    public function testAutowiresAndSharesServices(): void
    {
        $container = new Container();
        $service = $container->get(ContainerTestService::class);
        self::assertInstanceOf(ContainerTestDependency::class, $service->dependency);
        self::assertSame('default', $service->name);
        self::assertSame($service, $container->get(ContainerTestService::class));
        self::assertSame($service->dependency, $container->get(ContainerTestDependency::class));
    }

    public function testMakeCreatesNewInstancesWithExplicitParameters(): void
    {
        $container = new Container();
        $first = $container->make(ContainerTestService::class, ['name' => 'custom']);
        self::assertSame('custom', $first->name);
        self::assertNotSame($first, $container->make(ContainerTestService::class));
    }

    public function testFactoriesAndInstances(): void
    {
        $container = new Container();
        $container->set('answer', static fn (): int => 42);
        $container->instance('name', 'stuck');
        self::assertSame(42, $container->get('answer'));
        self::assertSame('stuck', $container->get('name'));
        self::assertTrue($container->bound('answer'));
        self::assertFalse($container->bound(ContainerTestDependency::class));
        self::assertTrue($container->has(ContainerTestDependency::class));
    }

    public function testUnknownServiceThrowsNotFound(): void
    {
        $this->expectException(NotFoundExceptionInterface::class);
        (new Container())->get('Does\\Not\\Exist');
    }

    public function testCircularDependencyIsDetected(): void
    {
        $container = new Container();
        $container->set('a', static fn (Container $c): mixed => $c->get('b'));
        $container->set('b', static fn (Container $c): mixed => $c->get('a'));
        $this->expectException(ContainerExceptionInterface::class);
        $container->get('a');
    }

    public function testUnresolvableParameterThrows(): void
    {
        $this->expectException(ContainerExceptionInterface::class);
        (new Container())->get(ContainerTestUnresolvable::class);
    }
}

final class ContainerTestDependency
{
}

final class ContainerTestService
{
    public function __construct(public readonly ContainerTestDependency $dependency, public readonly string $name = 'default')
    {
    }
}

final class ContainerTestUnresolvable
{
    public function __construct(public readonly string $required)
    {
    }
}
