<?php

declare(strict_types=1);

namespace Stuck1A\StuckMVC\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use Stuck1A\StuckMVC\Core\Config\Config;

final class ConfigTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/stuckmvc-config-' . bin2hex(random_bytes(4));
        mkdir($this->dir);
        file_put_contents($this->dir . '/configs.php', '<?php return ' . var_export([
            'application' => ['debug' => false, 'theme' => 'classic'],
            'database'    => ['host' => 'localhost', 'name' => '', 'pass' => ''],
            'paths'       => ['css' => 'out/src/<themename>/css'],
            'list'        => ['a', 'b', 'c'],
        ], true) . ';');
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir . '/*') ?: []);
        rmdir($this->dir);
    }

    public function testDotNotationAndDefaults(): void
    {
        $config = Config::load($this->dir);
        self::assertSame('localhost', $config->get('database.host'));
        self::assertSame(['host' => 'localhost', 'name' => '', 'pass' => ''], $config->get('database'));
        self::assertNull($config->get('database.missing'));
        self::assertSame('x', $config->get('missing.deeply.nested', 'x'));
        self::assertTrue($config->has('database.name'));
        self::assertFalse($config->has('database.port'));
        self::assertFalse($config->isDebug());
    }

    public function testLocalConfigOverridesRecursivelyAndReplacesLists(): void
    {
        file_put_contents($this->dir . '/configs.local.php', '<?php return ' . var_export([
            'application' => ['debug' => true],
            'database'    => ['name' => 'db', 'pass' => 'secret'],
            'list'        => ['z'],
        ], true) . ';');
        $config = Config::load($this->dir);
        self::assertTrue($config->isDebug());
        self::assertSame('classic', $config->get('application.theme'));
        self::assertSame(['host' => 'localhost', 'name' => 'db', 'pass' => 'secret'], $config->get('database'));
        self::assertSame(['z'], $config->get('list'));
    }

    public function testThemeTagIsReplaced(): void
    {
        $config = Config::load($this->dir)->with(['application' => ['theme' => 'modern']]);
        self::assertSame('out/src/modern/css', $config->get('paths.css'));
    }

    public function testInvalidThemeNameIsRejected(): void
    {
        $this->expectException(\RuntimeException::class);
        Config::load($this->dir)->with(['application' => ['theme' => '../../etc']]);
    }

    public function testMissingConfigFileFails(): void
    {
        $this->expectException(\RuntimeException::class);
        Config::load($this->dir . '/nope');
    }
}
