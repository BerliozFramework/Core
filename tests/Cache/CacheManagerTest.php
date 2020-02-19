<?php
/**
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2018 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

namespace Berlioz\Core\Tests\Cache;

use Berlioz\Core\Cache\CacheManager;
use Berlioz\Core\Directories\DefaultDirectories;
use Berlioz\Core\Directories\DirectoriesInterface;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheException;

class CacheManagerTest extends TestCase
{
    private $directories;
    private $cacheManager;

    protected function setUp(): void
    {
        $this->directories = new DefaultDirectories();
        $this->cacheManager = new CacheManager($this->directories);
    }

    protected function getDirectories(): DirectoriesInterface
    {
        return $this->directories;
    }

    protected function getCacheManager(): CacheManager
    {
        return $this->cacheManager;
    }

    public function providerSet()
    {
        return [['key', 'value', true],
                ['key-key', new \stdClass(), true],
                [['array'], 'value', false],
                [new \stdClass(), 'value', false],
                ['', 'value', false]];
    }

    public function provider()
    {
        return [['key', 'value', null],
                ['key2', new \stdClass(), null],
                ['key3', 1, 2],
                ['key4', ['array'], null],
                ['key5', 'value', null],
                ['key6', 'value', null],
                ['key7', 'value', null]];
    }

    public function providerMultiple()
    {
        return [[['key'  => 'value',
                  'key2' => new \stdClass(),
                  'key3' => 1],
                 null],

                [['key4' => ['array'],
                  'key5' => 'value',
                  'key6' => 'value'],
                 null],

                [['key7' => 'value'],
                 null]];
    }

    /**
     * @dataProvider providerSet
     */
    public function testSet($key, $value, $valid)
    {
        if (!$valid) {
            $this->expectException(CacheException::class);
        }

        $this->getCacheManager()->set($key, $value, 0);

        $this->assertTrue(true);
    }

    /**
     * @dataProvider provider
     */
    public function testGet($key, $value, $ttl)
    {
        $this->getCacheManager()->set($key, $value, $ttl);
        $this->assertEquals($value, $this->getCacheManager()->get($key));

        if (!is_null($ttl)) {
            sleep($ttl);
            $this->assertNull($this->getCacheManager()->get($key));
            $this->assertEquals('test', $this->getCacheManager()->get($key, 'test'));
        }
    }

    /**
     * @dataProvider provider
     */
    public function testHas($key, $value, $ttl)
    {
        if (is_null($ttl)) {
            $this->assertTrue($this->getCacheManager()->has($key));
        } else {
            $this->assertFalse($this->getCacheManager()->has($key));
        }
    }

    /**
     * @dataProvider provider
     */
    public function testDelete($key, $value, $ttl)
    {
        $this->assertTrue($this->getCacheManager()->delete($key));
        $this->assertFalse($this->getCacheManager()->has($key));
    }

    /**
     * @dataProvider providerMultiple
     */
    public function testSetMultiple($dataSet, $ttl)
    {
        $this->assertTrue($this->getCacheManager()->setMultiple($dataSet, $ttl));
    }

    /**
     * @dataProvider providerMultiple
     */
    public function testGetMultiple($dataSet)
    {
        $values = $this->getCacheManager()->getMultiple(array_keys($dataSet));
        $this->assertEquals($dataSet, $values);
    }

    /**
     * @dataProvider providerMultiple
     */
    public function testDeleteMultiple($dataSet)
    {
        $this->assertTrue($this->getCacheManager()->deleteMultiple(array_keys($dataSet)));
        $values = $this->getCacheManager()->getMultiple(array_keys($dataSet));
        $this->assertEquals([], array_filter($values));
    }

    public function testClear()
    {
        $cacheDirectory = $this->getDirectories()->getCacheDir() . DIRECTORY_SEPARATOR . CacheManager::CACHE_DIRECTORY;

        $this->assertTrue(is_dir($cacheDirectory));
        $this->assertTrue($this->getCacheManager()->clear());
        $this->assertFalse(is_dir($cacheDirectory));
    }
}
