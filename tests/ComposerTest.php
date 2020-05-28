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

use Berlioz\Core\Composer;
use Berlioz\Core\Exception\ComposerException;
use PHPUnit\Framework\TestCase;

class ComposerTest extends TestCase
{
    public function provider()
    {
        return [[new Composer(__DIR__ . '/_envTest/composer.json')]];
    }

    /**
     * @dataProvider provider
     */
    public function testSerializeUnserialize(Composer $composer)
    {
        $serialized = serialize($composer);
        $composer->getPackagesName();
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(Composer::class, $unserialized);

        $reflectionProperty = new \ReflectionProperty(Composer::class, 'composerJsonFilename');
        $reflectionProperty->setAccessible(true);
        $this->assertEquals(__DIR__ . '/_envTest/composer.json', $reflectionProperty->getValue($unserialized));
    }

    /**
     * @dataProvider provider
     */
    public function testGetPackagesName(Composer $composer)
    {
        $packages = $composer->getPackagesName();
        $this->assertEquals(['berlioz/config',
                             'berlioz/core',
                             'berlioz/service-container',
                             'psr/container',
                             'psr/log',
                             'psr/simple-cache',
                             'berlioz/fake1',
                             'berlioz/fake2'],
                            $packages);
    }

    /**
     * @dataProvider provider
     */
    public function testGetPackageVersion(Composer $composer)
    {
        $this->assertEquals(false, $composer->getPackageVersion('berlioz/unknown'));
        $this->assertEquals('1.0.1', $composer->getPackageVersion('psr/simple-cache'));
    }

    /**
     * @dataProvider provider
     */
    public function testGetProjectComposer(Composer $composer)
    {
        $this->assertNotEmpty($composer->getProjectComposer());
    }

    /**
     * @dataProvider provider
     */
    public function testGetPackage(Composer $composer)
    {
        $package = $composer->getPackage('berlioz/core');
        $this->assertIsArray($package);
    }

    /**
     * @dataProvider provider
     */
    public function testGetPackageNotExists(Composer $composer)
    {
        $this->expectException(ComposerException::class);
        $composer->getPackage('berlioz/unknown');
    }

    /**
     * @dataProvider provider
     */
    public function testGetPackages(Composer $composer)
    {
        $packages = $composer->getPackages();

        $this->assertIsArray($packages);
        $this->assertCount(8, $packages);
        $this->assertCount(8, array_filter($packages));
    }

    public function test__construct()
    {
        $composer = new Composer(__DIR__ . '/_envTest/composer.json');
        $this->assertInstanceOf(Composer::class, $composer);

        $this->expectException(ComposerException::class);
        $composer = new Composer(__DIR__ . '/_envTest/notexists.json');
        $composer->getPackagesName();
    }
}
