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

declare(strict_types=1);

namespace Berlioz\Core\Directories;

use Berlioz\Core\Exception\BerliozException;

/**
 * Class DefaultDirectories.
 *
 * @package Berlioz\Core\Directories
 */
class DefaultDirectories implements DirectoriesInterface
{
    /** @var string Working directory */
    protected $workingDirectory;
    /** @var string App directory */
    protected $appDirectory;
    /** @var string Config directory */
    protected $configDirectory;
    /** @var string Var directory */
    protected $varDirectory;
    /** @var string Cache directory */
    protected $cacheDirectory;
    /** @var string Log directory */
    protected $logDirectory;
    /** @var string Debug directory */
    protected $debugDirectory;
    /** @var string Vendor directory */
    protected $vendorDirectory;

    /**
     * @inheritdoc
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function getWorkingDir(): string
    {
        if (null === $this->workingDirectory) {
            // Get current working directory or file directory
            if (($this->workingDirectory = getcwd()) === false) {
                throw new BerliozException('Unable to get current working directory');
            }
        }

        return $this->workingDirectory;
    }

    /**
     * Get library directory.
     *
     * @return string
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    protected function getLibraryDirectory(): string
    {
        $myComposerFilename = realpath(__DIR__ . '/../../composer.json');
        if (false === $myComposerFilename) {
            throw new BerliozException('Unable to find composer.json file of Core library');
        }

        return dirname($myComposerFilename);
    }

    /**
     * @inheritdoc
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function getAppDir(): string
    {
        if (!null === $this->appDirectory) {
            return $this->appDirectory;
        }

        // Search composer.json for app directory
        $directories = [$this->getLibraryDirectory(), dirname($_SERVER['SCRIPT_FILENAME'])];
        do {
            $directory = current($directories);
            do {
                $directoryBefore = $directory;
                $directory = @realpath($directory . DIRECTORY_SEPARATOR . '..');

                if (file_exists($composerFilename = $directory . DIRECTORY_SEPARATOR . 'composer.json')) {
                    $this->appDirectory = $directory;
                    break(2);
                }
            } while ($directory !== false && $directoryBefore != $directory);
        } while (next($directories));

        if (null === $this->appDirectory) {
            $this->appDirectory = $this->getWorkingDir();
        }

        return $this->appDirectory;
    }

    /**
     * Get config directory.
     *
     * @return string
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function getConfigDir(): string
    {
        if (null === $this->configDirectory) {
            $this->configDirectory = $this->getAppDir() . DIRECTORY_SEPARATOR . 'config';
        }

        return $this->configDirectory;
    }

    /**
     * Get var directory.
     *
     * @return string
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function getVarDir(): string
    {
        if (null === $this->varDirectory) {
            $this->varDirectory = $this->getAppDir() . DIRECTORY_SEPARATOR . 'var';
        }

        return $this->varDirectory;
    }

    /**
     * Get cache directory.
     *
     * @return string
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function getCacheDir(): string
    {
        if (null === $this->cacheDirectory) {
            $this->cacheDirectory = $this->getVarDir() . DIRECTORY_SEPARATOR . 'cache';
        }

        return $this->cacheDirectory;
    }

    /**
     * Get log directory.
     *
     * @return string
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function getLogDir(): string
    {
        if (null === $this->logDirectory) {
            $this->logDirectory = $this->getVarDir() . DIRECTORY_SEPARATOR . 'log';
        }

        return $this->logDirectory;
    }

    /**
     * Get debug directory.
     *
     * @return string
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function getDebugDir(): string
    {
        if (null === $this->debugDirectory) {
            $this->debugDirectory = $this->getVarDir() . DIRECTORY_SEPARATOR . 'debug';
        }

        return $this->debugDirectory;
    }

    /**
     * Get vendor directory.
     *
     * @return string
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function getVendorDir(): string
    {
        if (null === $this->vendorDirectory) {
            $this->vendorDirectory = $this->getAppDir() . DIRECTORY_SEPARATOR . 'vendor';
        }

        return $this->vendorDirectory;
    }
}