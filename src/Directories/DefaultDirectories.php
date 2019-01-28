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
     */
    public function getWorkingDir(): string
    {
        if (is_null($this->workingDirectory)) {
            // Get document root from server configuration
            if (getenv('DOCUMENT_ROOT') !== false) {
                return $this->workingDirectory = rtrim(getenv('DOCUMENT_ROOT'), '\\/');
            }

            return $this->workingDirectory = getcwd() ?: __DIR__;
        }

        return $this->workingDirectory;
    }

    /**
     * @inheritdoc
     */
    public function getAppDir(): string
    {
        if (!is_null($this->appDirectory)) {
            return $this->appDirectory;
        }

        $myComposerFilename = realpath(__DIR__ . '/../../composer.json');

        // Search composer.json for app directory
        $directory = $this->getWorkingDir();
        do {
            $directoryBefore = $directory;

            if (file_exists($composerFilename = $directory . DIRECTORY_SEPARATOR . 'composer.json')) {
                if ($composerFilename != $myComposerFilename) {
                    $this->appDirectory = $directory;
                    break;
                }
            }

            $directory = @realpath($directory . DIRECTORY_SEPARATOR . '..');
        } while ($directory !== false && $directoryBefore != $directory);

        if (is_null($this->appDirectory)) {
            $this->appDirectory = $this->getWorkingDir();
        }

        return $this->appDirectory;
    }

    /**
     * Get config directory.
     *
     * @return string
     */
    public function getConfigDir(): string
    {
        if (is_null($this->configDirectory)) {
            $this->configDirectory = $this->getAppDir() . DIRECTORY_SEPARATOR . 'config';
        }

        return $this->configDirectory;
    }

    /**
     * Get var directory.
     *
     * @return string
     */
    public function getVarDir(): string
    {
        if (is_null($this->varDirectory)) {
            $this->varDirectory = $this->getAppDir() . DIRECTORY_SEPARATOR . 'var';
        }

        return $this->varDirectory;
    }

    /**
     * Get cache directory.
     *
     * @return string
     */
    public function getCacheDir(): string
    {
        if (is_null($this->cacheDirectory)) {
            $this->cacheDirectory = $this->getVarDir() . DIRECTORY_SEPARATOR . 'cache';
        }

        return $this->cacheDirectory;
    }

    /**
     * Get log directory.
     *
     * @return string
     */
    public function getLogDir(): string
    {
        if (is_null($this->logDirectory)) {
            $this->logDirectory = $this->getVarDir() . DIRECTORY_SEPARATOR . 'log';
        }

        return $this->logDirectory;
    }

    /**
     * Get debug directory.
     *
     * @return string
     */
    public function getDebugDir(): string
    {
        if (is_null($this->debugDirectory)) {
            $this->debugDirectory = $this->getVarDir() . DIRECTORY_SEPARATOR . 'debug';
        }

        return $this->debugDirectory;
    }

    /**
     * Get vendor directory.
     *
     * @return string
     */
    public function getVendorDir(): string
    {
        if (is_null($this->vendorDirectory)) {
            $this->vendorDirectory = $this->getAppDir() . DIRECTORY_SEPARATOR . 'vendor';
        }

        return $this->vendorDirectory;
    }
}