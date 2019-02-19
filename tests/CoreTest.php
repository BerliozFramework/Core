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

namespace Berlioz\Core\Tests;

use Berlioz\Config\ConfigInterface;
use Berlioz\Core\Composer;
use Berlioz\Core\Config;
use Berlioz\Core\Core;
use Berlioz\Core\Debug;
use Berlioz\Core\Directories\DefaultDirectories;
use Berlioz\Core\Directories\DirectoriesInterface;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

class CoreTest extends TestCase
{
    public function provider()
    {
        $directories = new DefaultDirectories();
        $core = new Core($directories);

        return [[$core, $directories]];
    }

    /**
     * @dataProvider provider
     */
    public function test__construct(Core $core)
    {
        // @todo
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @dataProvider provider
     */
    public function test__destruct(Core $core)
    {
        // @todo
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @dataProvider provider
     */
    public function testSerialize(Core $core)
    {
        // @todo
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @dataProvider provider
     */
    public function testUnserialize(Core $core)
    {
        // @todo
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @dataProvider provider
     */
    public function testGetCacheManager(Core $core)
    {
        $this->assertInstanceOf(CacheInterface::class, $core->getCacheManager());
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
        $this->assertInstanceOf(Config::class, $core->getConfig());
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
        // @todo
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @dataProvider provider
     */
    public function testGetDirectories(Core $core, DirectoriesInterface $directories)
    {
        $this->assertInstanceOf(DirectoriesInterface::class, $core->getDirectories());
        //var_dump('###############################', $directories, $core->getDirectories());
        $this->assertEquals($directories, $core->getDirectories());
    }

    /**
     * @dataProvider provider
     */
    public function testOnTerminate(Core $core)
    {
        // @todo
        $this->markTestIncomplete('This test has not been implemented yet.');
    }
}
