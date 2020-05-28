<?php
/**
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2020 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

namespace Berlioz\Core\Tests\Cache;

use Berlioz\Core\Cache\NullCacheManager;
use PHPUnit\Framework\TestCase;

class NullCacheManagerTest extends TestCase
{
    public function testGet()
    {
        $nullCache = new NullCacheManager();

        $this->assertEquals('foo', $nullCache->get('bar', 'foo'));
    }

    public function testSet()
    {
        $nullCache = new NullCacheManager();

        $this->assertTrue($nullCache->set('foo', 'bar'));
    }

    public function testDelete()
    {
        $nullCache = new NullCacheManager();

        $this->assertTrue($nullCache->delete('foo'));
    }

    public function testHas()
    {
        $nullCache = new NullCacheManager();

        $this->assertFalse($nullCache->has('foo'));
    }

    public function testGetMultiple()
    {
        $nullCache = new NullCacheManager();

        $this->assertEquals(['bar' => 'qux', 'foo' => 'qux'], $nullCache->getMultiple(['bar', 'foo'], 'qux'));
    }

    public function testSetMultiple()
    {
        $nullCache = new NullCacheManager();

        $this->assertTrue($nullCache->setMultiple(['bar' => 'qux', 'foo' => 'qux']));
    }

    public function testClear()
    {
        $nullCache = new NullCacheManager();

        $this->assertTrue($nullCache->clear());
    }

    public function testDeleteMultiple()
    {
        $nullCache = new NullCacheManager();

        $this->assertTrue($nullCache->deleteMultiple(['bar', 'foo']));
    }
}
