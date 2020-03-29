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

namespace Berlioz\Core\Tests\Directories;

use Berlioz\Core\Directories\DefaultDirectories;
use PHPUnit\Framework\TestCase;

class DefaultDirectoriesTest extends TestCase
{
    private function getAppDirectory(): string
    {
        return realpath(__DIR__ . '/../_envTest');
    }

    private function getDefaultDirectories(): DefaultDirectories
    {
        chdir($workingDirectory = realpath($this->getAppDirectory() . DIRECTORY_SEPARATOR . 'public'));
        return new FakeDefaultDirectories();
    }

    public function testGetWorkingDir()
    {
        $this->assertEquals(
            realpath($this->getAppDirectory() . DIRECTORY_SEPARATOR . 'public'),
            $this->getDefaultDirectories()->getWorkingDir()
        );
    }

    public function testGetVarDir()
    {
        $this->assertEquals(
            realpath($this->getAppDirectory() . DIRECTORY_SEPARATOR . 'var'),
            $this->getDefaultDirectories()->getVarDir()
        );
    }

    public function testGetDebugDir()
    {
        $this->assertEquals(
            realpath($this->getAppDirectory() . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'debug'),
            $this->getDefaultDirectories()->getDebugDir()
        );
    }

    public function testGetCacheDir()
    {
        $this->assertEquals(
            realpath($this->getAppDirectory() . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'cache'),
            $this->getDefaultDirectories()->getCacheDir()
        );
    }

    public function testGetLogDir()
    {
        $this->assertEquals(
            realpath($this->getAppDirectory() . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'log'),
            $this->getDefaultDirectories()->getLogDir()
        );
    }

    public function testGetConfigDir()
    {
        $this->assertEquals(
            realpath($this->getAppDirectory() . DIRECTORY_SEPARATOR . 'config'),
            $this->getDefaultDirectories()->getConfigDir()
        );
    }

    public function testGetAppDir()
    {
        $this->assertEquals(
            realpath($this->getAppDirectory()),
            $this->getDefaultDirectories()->getAppDir()
        );
    }

    public function testGetVendorDir()
    {
        $this->assertEquals(
            realpath($this->getAppDirectory() . DIRECTORY_SEPARATOR . 'vendor'),
            $this->getDefaultDirectories()->getVendorDir()
        );
    }
}
