<?php
/*
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2021 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

namespace Berlioz\Core\Tests\Cache;

use Berlioz\Core\Cache\FileCacheDriver;
use Berlioz\Core\Directories\DefaultDirectories;
use Berlioz\Core\Tests\Directories\FakeDefaultDirectories;
use Psr\SimpleCache\CacheInterface;

class FileCacheDriverTest extends AbstractCacheDriverTest
{
    protected static ?FileCacheDriver $cacheDriver = null;
    protected static DefaultDirectories $directories;

    protected function getCacheDriver(): CacheInterface
    {
        if (null === self::$cacheDriver) {
            self::$directories = new FakeDefaultDirectories();
            self::$cacheDriver = new FileCacheDriver(self::$directories);
        }

        return self::$cacheDriver;
    }

    public function testClear()
    {
        $this->getCacheDriver()->set('foo', 'bar');
        $cacheDirectory = self::$directories->getCacheDir() . DIRECTORY_SEPARATOR . FileCacheDriver::CACHE_DIRECTORY;

        $this->assertTrue(is_dir($cacheDirectory));
        $this->assertTrue($this->getCacheDriver()->clear());
        $this->assertFalse(is_dir($cacheDirectory));
    }
}