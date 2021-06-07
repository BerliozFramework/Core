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

declare(strict_types=1);

namespace Berlioz\Core;

use Berlioz\Core\Directories\DirectoriesInterface;
use League\Flysystem\Filesystem as FlyFilesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\MountManager;

/**
 * Class Filesystem.
 */
class Filesystem extends MountManager
{
    /**
     * Filesystem constructor.
     *
     * @param DirectoriesInterface $directories
     */
    public function __construct(DirectoriesInterface $directories)
    {
        $directoriesArray = array_map(
            fn(string $path) => new FlyFilesystem(new LocalFilesystemAdapter($path)),
            $directories->getArrayCopy()
        );

        parent::__construct($directoriesArray);
    }
}