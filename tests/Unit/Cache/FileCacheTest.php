<?php

declare(strict_types=1);

namespace Stuck1A\StuckMVC\Tests\Unit\Cache;

use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\InvalidArgumentException;
use Stuck1A\StuckMVC\Core\Cache\FileCache;
use Stuck1A\StuckMVC\Core\Cache\NullCache;

final class FileCacheTest extends TestCase
{
    private string $dir;

    private FileCache $cache;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/stuckmvc-cache-' . bin2hex(random_bytes(4));
        $this->cache = new FileCache($this->dir);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir . '/*') ?: []);
        @rmdir($this->dir);
    }

    public function testSetGetDelete(): void
    {
        self::assertSame('default', $this->cache->get('mappings', 'default'));
        self::assertTrue($this->cache->set('mappings', ['a' => [1, 2, 3]]));
        self::assertSame(['a' => [1, 2, 3]], $this->cache->get('mappings'));
        self::assertTrue($this->cache->has('mappings'));
        self::assertTrue($this->cache->delete('mappings'));
        self::assertFalse($this->cache->has('mappings'));
        self::assertTrue($this->cache->delete('mappings'), 'deleting a missing item is no error');
    }

    public function testStoredFalseAndNullAreReturned(): void
    {
        $this->cache->set('false', false);
        $this->cache->set('null', null);
        self::assertFalse($this->cache->get('false', 'default'));
        self::assertNull($this->cache->get('null', 'default'));
        self::assertTrue($this->cache->has('null'));
    }

    public function testPersistsAcrossInstances(): void
    {
        $this->cache->set('key', 'value');
        self::assertSame('value', (new FileCache($this->dir))->get('key'));
    }

    public function testTtl(): void
    {
        $this->cache->set('expired', 'x', 0);
        self::assertFalse($this->cache->has('expired'), 'TTL <= 0 deletes the item');
        $this->cache->set('valid', 'x', new \DateInterval('PT1H'));
        self::assertSame('x', $this->cache->get('valid'));
        // manipulate the expiry timestamp to simulate an expired item
        $file = glob($this->dir . '/*.cache')[0];
        file_put_contents($file, serialize(['expires' => time() - 1, 'value' => 'x']));
        self::assertSame('gone', $this->cache->get('valid', 'gone'));
        self::assertFileDoesNotExist($file);
    }

    public function testCorruptedItemIsTreatedAsMiss(): void
    {
        $this->cache->set('key', 'value');
        file_put_contents(glob($this->dir . '/*.cache')[0], 'garbage');
        self::assertSame('miss', $this->cache->get('key', 'miss'));
    }

    public function testObjectsInCacheFilesAreNotInstantiated(): void
    {
        $this->cache->set('key', 'value');
        file_put_contents(glob($this->dir . '/*.cache')[0], serialize(['expires' => null, 'value' => new \ArrayObject([1])]));
        self::assertInstanceOf(\__PHP_Incomplete_Class::class, $this->cache->get('key'));
    }

    public function testMultipleAndClear(): void
    {
        self::assertTrue($this->cache->setMultiple(['a' => 1, 'b' => 2]));
        self::assertSame(['a' => 1, 'b' => 2, 'c' => 'd'], $this->cache->getMultiple(['a', 'b', 'c'], 'd'));
        self::assertTrue($this->cache->deleteMultiple(['a']));
        self::assertFalse($this->cache->has('a'));
        self::assertTrue($this->cache->clear());
        self::assertFalse($this->cache->has('b'));
        self::assertSame([], glob($this->dir . '/*') ?: []);
    }

    public function testInvalidKeysAreRejected(): void
    {
        foreach (['', 'a/b', 'a:b', '{x}', 'a@b', '..\\x'] as $key) {
            try {
                $this->cache->get($key);
                self::fail("Key '{$key}' must be rejected.");
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testNullCacheStoresNothing(): void
    {
        $cache = new NullCache();
        self::assertFalse($cache->set('a', 1));
        self::assertSame('d', $cache->get('a', 'd'));
        self::assertFalse($cache->has('a'));
        self::assertSame(['a' => null], $cache->getMultiple(['a']));
    }
}
