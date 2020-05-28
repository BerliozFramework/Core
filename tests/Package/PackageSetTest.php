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

namespace Berlioz\Core\Tests\Package;

use Berlioz\Config\JsonConfig;
use Berlioz\Core\Composer;
use Berlioz\Core\Core;
use Berlioz\Core\Exception\BerliozException;
use Berlioz\Core\Exception\PackageException;
use Berlioz\Core\Package\PackageSet;
use Berlioz\Core\Tests\Directories\FakeDefaultDirectories;
use PHPUnit\Framework\TestCase;

class PackageSetTest extends TestCase
{
    public function testAddPackage()
    {
        $packageSet = new PackageSet();
        $packageSet->addPackage(FakePackage1::class);

        $this->assertCount(1, $packageSet->getPackages());

        $this->expectException(PackageException::class);
        $packageSet->addPackage(\stdClass::class);
    }

    public function testConfig()
    {
        $packageSet = new PackageSet();
        $packageSet->addPackage(FakePackage1::class);
        $packageSet->addPackage(FakePackage2::class);
        $packageSet->addPackage(FakePackage3::class);

        $packageSet->config($config = new JsonConfig('{}'));

        $this->assertEquals('qux', $config->get('package1'));
        $this->assertEquals('qux', $config->get('package2'));
        $this->assertEquals('bar', $config->get('package3.foo'));
    }

    public function testConfigError()
    {
        $packageSet = new PackageSet();
        $packageSet->addPackage(FakePackageBad::class);

        $this->expectException(BerliozException::class);

        $packageSet->config($config = new JsonConfig('{}'));
    }

    public function testRegister()
    {
        $core = new Core(new FakeDefaultDirectories());

        $packageSet = new PackageSet();
        $packageSet->addPackage(FakePackage1::class);
        $packageSet->register($core->getServiceContainer()->getInstantiator());

        $this->assertTrue($core->getServiceContainer()->has('date'));
    }

    public function testInit()
    {
        $core = new Core(new FakeDefaultDirectories());

        $packageSet = new PackageSet();
        $packageSet->addPackage(FakePackage1::class);

        FakePackage1::$foo = false;

        $packageSet->init($core->getServiceContainer()->getInstantiator());

        $this->assertTrue(FakePackage1::$foo);
    }

    public function testSerialization()
    {
        $composer = new Composer(__DIR__ . '/../_envTest/composer.json');
        $packageSet = new PackageSet();
        $packageSet->addPackagesFromComposer($composer);

        $packageSet2 = unserialize(serialize($packageSet));
        $this->assertEquals($packageSet->getPackages(), $packageSet2->getPackages());
    }

    public function testAddPackagesFromConfig()
    {
        $config = new JsonConfig(
            '{"packages": ["Berlioz\\\\Core\\\\Tests\\\\Package\\\\FakePackage1", "Berlioz\\\\Core\\\\Tests\\\\Package\\\\FakePackage2"]}'
        );
        $packageSet = new PackageSet();
        $packageSet->addPackagesFromConfig($config);

        $this->assertCount(2, $packageSet->getPackages());
        $this->assertEquals(["Berlioz\\Core\\Tests\\Package\\FakePackage1","Berlioz\\Core\\Tests\\Package\\FakePackage2"], $packageSet->getPackages());
    }

    public function testAddPackagesFromComposer()
    {
        $composer = new Composer(__DIR__ . '/../_envTest/composer.json');
        $packageSet = new PackageSet();
        $packageSet->addPackagesFromComposer($composer);

        $this->assertCount(2, $packageSet->getPackages());
        $this->assertEquals(["Berlioz\\Core\\Tests\\Package\\FakePackage1","Berlioz\\Core\\Tests\\Package\\FakePackage2"], $packageSet->getPackages());
    }
}
