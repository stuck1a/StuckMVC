<?php

declare(strict_types=1);

namespace Stuck1A\StuckMVC\Tests\Unit\Log;

use PHPUnit\Framework\TestCase;
use Psr\Log\InvalidArgumentException;
use Stuck1A\StuckMVC\Core\Log\FileLogger;

final class FileLoggerTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        $this->file = sys_get_temp_dir() . '/stuckmvc-log-' . bin2hex(random_bytes(4)) . '/app.log';
    }

    protected function tearDown(): void
    {
        @unlink($this->file);
        @rmdir(dirname($this->file));
    }

    public function testFiltersByLevelAndInterpolates(): void
    {
        $logger = new FileLogger($this->file, 'warning');
        $logger->info('not logged');
        $logger->warning('User {user} failed {count} times, flag {flag}', ['user' => 'stuck', 'count' => 3, 'flag' => false]);
        $content = (string) file_get_contents($this->file);
        self::assertStringNotContainsString('not logged', $content);
        self::assertStringContainsString('[WARNING] User stuck failed 3 times, flag false', $content);
    }

    public function testLineBreaksCannotForgeEntries(): void
    {
        $logger = new FileLogger($this->file, 'debug');
        $logger->notice('Login for {login}', ['login' => "foo\n01.01.2000 00:00:00 [CRITICAL] forged"]);
        self::assertCount(1, file($this->file));
    }

    public function testExceptionIsAppended(): void
    {
        $logger = new FileLogger($this->file, 'debug');
        $logger->error('Failed', ['exception' => new \RuntimeException('boom', 0, new \LogicException('inner'))]);
        $content = (string) file_get_contents($this->file);
        self::assertStringContainsString('RuntimeException: boom', $content);
        self::assertStringContainsString('LogicException: inner', $content);
    }

    public function testUnknownLevelThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new FileLogger($this->file))->log('fatal', 'x');
    }
}
