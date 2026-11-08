<?php

declare(strict_types=1);

namespace Stuck1A\StuckMVC\Tests\Unit\Routing;

use PHPUnit\Framework\TestCase;
use Stuck1A\StuckMVC\Core\Config\Config;
use Stuck1A\StuckMVC\Core\Routing\Route;
use Stuck1A\StuckMVC\Core\Routing\RouteCollection;
use Stuck1A\StuckMVC\Core\Routing\UrlGenerator;

final class RoutingTest extends TestCase
{
    private function collection(): RouteCollection
    {
        return new RouteCollection([
            new Route('', 'home.tpl', 'Home'),
            new Route('home', 'home.tpl', 'Home'),
            new Route('music', 'music/index.tpl', 'Musik'),
            new Route('music/index', 'music/index.tpl', 'Musik'),
            new Route('music/byartist', 'music/byartist.tpl', 'Nach Interpret'),
            new Route('a/b/c', 'c.tpl', 'C'),
        ]);
    }

    public function testTrailContainsAllExistingAncestors(): void
    {
        $routes = $this->collection();
        $titles = static fn (array $trail): array => array_map(static fn (Route $r): string => $r->path, $trail);
        self::assertSame(['', 'music', 'music/byartist'], $titles($routes->trail($routes->get('music/byartist'))));
        self::assertSame(['', 'music'], $titles($routes->trail($routes->get('music'))));
        self::assertSame([''], $titles($routes->trail($routes->get(''))));
        self::assertSame([''], $titles($routes->trail($routes->get('home'))), 'aliases of the start page are merged');
        self::assertSame(['', 'music'], $titles($routes->trail($routes->get('music/index'))), 'directory index is merged');
        self::assertSame(['', 'a/b/c'], $titles($routes->trail($routes->get('a/b/c'))), 'missing levels are skipped');
    }

    public function testSerialization(): void
    {
        $routes = $this->collection();
        $copy = RouteCollection::fromArray($routes->toArray());
        self::assertEquals($routes->all(), $copy->all());
        self::assertCount(6, $copy);
    }

    public function testRouteFlags(): void
    {
        self::assertTrue((new Route('music', 't', 'M'))->isPublicContent());
        self::assertFalse((new Route('error/404', 't', 'E'))->isPublicContent());
        self::assertFalse((new Route('admin', 't', 'A', Route::SECTION_ADMIN))->isPublicContent());
        self::assertFalse((new Route('account', 't', 'K'))->isPublicContent());
        self::assertFalse((new Route('account/reset', 't', 'K'))->isPublicContent());
        self::assertTrue((new Route('admin', 't', 'A', Route::SECTION_ADMIN))->isAdmin());
    }

    public function testParameterRouteMatchesOnePathSegment(): void
    {
        $routes = new RouteCollection([new Route('user/{username}', 'profile.tpl', 'Profil')]);
        self::assertSame('user/erika', $routes->get('user/erika')?->path);
        self::assertTrue($routes->has('user/erika'));
        self::assertNull($routes->get('user/a/b'));
        self::assertFalse($routes->get('user/{username}')->isPublicContent());
    }

    public function testUrlGenerator(): void
    {
        $config = new Config(['paths' => ['img' => 'out/img', 'empty' => '']]);
        $url = new UrlGenerator($config, '/sub/');
        self::assertSame('/sub', $url->basePath());
        self::assertSame('/sub/', $url->to());
        self::assertSame('/sub/music/byartist', $url->to('/music/byartist/'));
        self::assertSame('/sub/search?q=a+b%26c', $url->to('search', ['q' => 'a b&c']));
        self::assertSame('/sub/foo%22bar', $url->to('foo"bar'));
        self::assertSame('/sub/out/img/', $url->dir('img'));
        self::assertSame('/sub/', $url->dir('empty'));
    }

    public function testBasePathDetection(): void
    {
        $config = new Config(['application' => ['base_path' => null]]);
        self::assertSame('', UrlGenerator::detectBasePath($config, ['SCRIPT_NAME' => '/index.php']));
        self::assertSame('/sub', UrlGenerator::detectBasePath($config, ['SCRIPT_NAME' => '/sub/index.php']));
        self::assertSame('/fixed', UrlGenerator::detectBasePath(new Config(['application' => ['base_path' => 'fixed/']]), []));
    }

    public function testLocalUrlValidation(): void
    {
        $url = new UrlGenerator(new Config([]), '');
        self::assertTrue($url->isLocal('/admin'));
        self::assertTrue($url->isLocal('/admin?x=1'));
        foreach (['', 'admin', '//evil.example', 'https://evil.example', '/\\evil.example', "/foo\nbar", 'javascript:alert(1)'] as $bad) {
            self::assertFalse($url->isLocal($bad), $bad);
        }
    }
}
