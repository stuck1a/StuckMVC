<?php

declare(strict_types=1);

namespace Stuck1A\StuckMVC\Tests\Unit\Http;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Stuck1A\StuckMVC\Core\Http\Request;

final class RequestTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function paths(): iterable
    {
        yield 'root'              => ['/', '', ''];
        yield 'empty'             => ['', '', ''];
        yield 'simple'            => ['/music', '', 'music'];
        yield 'nested'            => ['/music/byartist', '', 'music/byartist'];
        yield 'trailing slash'    => ['/music/', '', 'music'];
        yield 'double slashes'    => ['//music///byartist//', '', 'music/byartist'];
        yield 'query string'      => ['/search?q=foo&x=1', '', 'search'];
        yield 'encoded'           => ['/foo%20bar', '', 'foo bar'];
        yield 'base path'         => ['/stucksseiten/music', '/stucksseiten', 'music'];
        yield 'base path root'    => ['/stucksseiten', '/stucksseiten', ''];
        yield 'base path prefix'  => ['/stucksseitenfoo', '/stucksseiten', 'stucksseitenfoo'];
        yield 'absolute uri'      => ['https://example.com/music?x', '', 'music'];
    }

    #[DataProvider('paths')]
    public function testNormalizePath(string $uri, string $basePath, string $expected): void
    {
        self::assertSame($expected, Request::normalizePath($uri, $basePath));
    }

    public function testAccessors(): void
    {
        $request = Request::create('post', '/admin/login?redirect=/admin', ['login' => 'foo', 'arr' => ['x']], [
            'HTTPS' => 'on', 'HTTP_X_TEST' => 'yes',
        ]);
        self::assertTrue($request->isPost());
        self::assertSame('admin/login', $request->path);
        self::assertSame('/admin', $request->query('redirect'));
        self::assertSame('foo', $request->input('login'));
        self::assertNull($request->input('arr'), 'non-scalar input must not be returned as string');
        self::assertSame('fallback', $request->input('missing', 'fallback'));
        self::assertSame('yes', $request->header('X-Test'));
        self::assertTrue($request->isSecure());
        self::assertSame('127.0.0.1', $request->clientIp());
    }
}
