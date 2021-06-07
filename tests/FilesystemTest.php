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

namespace Berlioz\Core\Tests;

use Berlioz\Core\Filesystem;
use Berlioz\Core\Tests\Directories\FakeDefaultDirectories;
use League\Flysystem\MountManager;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class FilesystemTest extends TestCase
{
    public function testConstruct()
    {
        $filesystem = new Filesystem($directories = new FakeDefaultDirectories());
        $reflectionProperty = new ReflectionProperty(MountManager::class, 'filesystems');
        $reflectionProperty->setAccessible(true);

        $this->assertCount(count($directories->getArrayCopy()), $reflectionProperty->getValue($filesystem));
        $this->assertEquals(
            array_keys($directories->getArrayCopy()),
            array_keys($reflectionProperty->getValue($filesystem))
        );
    }
}
