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

namespace Berlioz\Core\TestProject;

use Berlioz\Core\Directories\DefaultDirectories;

class TestDirectories extends DefaultDirectories
{
    /**
     * @inheritdoc
     */
    public function getWorkingDir(): string
    {
        if (is_null($this->workingDirectory)) {
            $this->workingDirectory = realpath($this->getAppDir() . DIRECTORY_SEPARATOR . 'public');
        }

        return $this->workingDirectory;
    }

    /**
     * @inheritdoc
     */
    public function getAppDir(): string
    {
        if (is_null($this->appDirectory)) {
            $this->appDirectory = realpath(__DIR__ . DIRECTORY_SEPARATOR . '_envTest');
        }

        return $this->appDirectory;
    }
}