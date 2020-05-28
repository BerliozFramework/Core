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

namespace Berlioz\Core\Tests;

use Berlioz\Config\ConfigInterface;
use Berlioz\Core\Cache\NullCacheManager;
use Berlioz\Core\Composer;
use Berlioz\Core\Core;
use Berlioz\Core\Debug;
use Berlioz\Core\Directories\DirectoriesInterface;
use Berlioz\Core\Exception\BerliozException;
use Berlioz\Core\Package\PackageSet;
use Berlioz\Core\TestProject\ServiceBar;
use Berlioz\Core\TestProject\ServiceFoo;
use Berlioz\Core\TestProject\ServiceQuux;
use Berlioz\Core\TestProject\ServiceQux;
use Berlioz\Core\Tests\Directories\FakeDefaultDirectories;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

class CoreTest extends TestCase
{
    public function provider()
    {
        $directories = new FakeDefaultDirectories();
        $core = new Core($directories, true);
        $core2 = new Core($directories, false);

        return [[$core, $directories], [$core2, $directories]];
    }

    public function test__construct()
    {
        $directories = new FakeDefaultDirectories();
        $core = new Core($directories, false);
        $core2 = new Core($directories, true);

        $this->assertFalse($core->isCacheEnabled());
        $this->assertTrue($core2->isCacheEnabled());
        $this->assertEquals($directories, $core->getDirectories());
        $this->assertEquals($directories, $core2->getDirectories());
    }

    /**
     * @dataProvider provider
     */
    public function testSerialize(Core $core)
    {
        $this->expectException(BerliozException::class);
        $core->serialize();
    }

    /**
     * @dataProvider provider
     */
    public function testUnserialize(Core $core)
    {
        $this->expectException(BerliozException::class);
        $core->unserialize('');
    }

    /**
     * @dataProvider provider
     */
    public function testGetCacheManager(Core $core)
    {
        if ($core->isCacheEnabled()) {
            $this->assertInstanceOf(CacheInterface::class, $core->getCacheManager());
        }
        if (!$core->isCacheEnabled()) {
            $this->assertInstanceOf(NullCacheManager::class, $core->getCacheManager());
        }
    }

    /**
     * @dataProvider provider
     */
    public function testGetDebug(Core $core)
    {
        $this->assertInstanceOf(Debug::class, $core->getDebug());
    }

    /**
     * @dataProvider provider
     */
    public function testGetComposer(Core $core)
    {
        $this->assertInstanceOf(Composer::class, $core->getComposer());
    }

    /**
     * @dataProvider provider
     */
    public function testGetConfig(Core $core)
    {
        $this->assertInstanceOf(ConfigInterface::class, $core->getConfig());
    }

    /**
     * @dataProvider provider
     */
    public function testGetServiceContainer(Core $core)
    {
        $this->assertInstanceOf(Composer::class, $core->getComposer());
    }

    /**
     * @dataProvider provider
     */
    public function testInitServiceContainer(Core $core)
    {
        $serviceQux = $core->getServiceContainer()->get(ServiceQux::class);
        $serviceFoo = $core->getServiceContainer()->get(ServiceFoo::class);
        $serviceBar = $core->getServiceContainer()->get(ServiceBar::class);
        $serviceQuux = $core->getServiceContainer()->get(ServiceQuux::class);

        $this->assertInstanceOf(ServiceQux::class, $serviceQux);
        $this->assertInstanceOf(ServiceFoo::class, $serviceFoo);
        $this->assertInstanceOf(ServiceBar::class, $serviceBar);
        $this->assertInstanceOf(ServiceQuux::class, $serviceQuux);
        $this->assertSame($serviceQux->serviceBar, $serviceBar);
        $this->assertEquals(7, $serviceQux->increment);
        $this->assertTrue($serviceQuux->factory);
    }

    /**
     * @dataProvider provider
     */
    public function testLocale(Core $core)
    {
        $this->assertEquals(\Locale::getDefault(), $core->getLocale());
        $this->assertEquals($core, $core->setLocale('fr_FR'));
        $this->assertEquals('fr_FR', $core->getLocale());
    }

    /**
     * @dataProvider provider
     */
    public function testGetPackage(Core $core)
    {
        $this->assertInstanceOf(PackageSet::class, $core->getPackages());
    }

    /**
     * @dataProvider provider
     */
    public function testGetDirectories(Core $core, DirectoriesInterface $directories)
    {
        $this->assertInstanceOf(DirectoriesInterface::class, $core->getDirectories());
        $this->assertEquals($directories, $core->getDirectories());
    }

    /**
     * @dataProvider provider
     */
    public function testOnTerminate(Core $core)
    {
        $foo = false;
        $bar = false;
        $core
            ->onTerminate(function () use (&$foo) {
                $foo = true;
            })
            ->onTerminate(function () use (&$bar) {
                $bar = true;
            });

        $core->terminate();

        $this->assertTrue($foo);
        $this->assertTrue($bar);
    }
}
