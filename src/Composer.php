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

namespace Berlioz\Core;

use Berlioz\Core\Exception\ComposerException;

/**
 * Class Composer.
 *
 * @package Berlioz\Core
 */
class Composer implements \Serializable
{
    /** @var string Composer JSON filename */
    private $composerJsonFilename;
    /** @var array Composer JSON */
    private $composerJson;
    /** @var array Composer Lock */
    private $composerLock;
    /** @var array Packages */
    private $packages;

    /**
     * Composer constructor.
     *
     * @param string $composerJsonFilename
     *
     * @throws \Berlioz\Core\Exception\ComposerException
     */
    public function __construct(string $composerJsonFilename)
    {
        $this->composerJsonFilename = $composerJsonFilename;
        $this->init();
    }

    /////////////////////
    /// SERIALIZATION ///
    /////////////////////

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        return serialize(['composerJsonFilename' => $this->composerJsonFilename,
                          'composerJson'         => $this->composerJson,
                          'composerLock'         => $this->composerLock,
                          'packages'             => $this->packages]);
    }

    /**
     * @inheritdoc
     */
    public function unserialize($serialized): void
    {
        $unserialized = unserialize($serialized);

        $this->composerJsonFilename = $unserialized['composerJsonFilename'];
        $this->composerJson = $unserialized['composerJson'];
        $this->composerLock = $unserialized['composerLock'];
        $this->packages = $unserialized['packages'];
    }

    //////////////////////
    /// INITIALIZATION ///
    //////////////////////

    /**
     * Init.
     *
     * @throws \Berlioz\Core\Exception\ComposerException
     */
    private function init(): void
    {
        // Load composer.json
        $this->composerJson = $this->loadJsonFile($this->composerJsonFilename);

        // Reconstitute the composer.lock filename
        $composerLockFilename = dirname($this->composerJsonFilename) .
                                DIRECTORY_SEPARATOR .
                                basename($this->composerJsonFilename, '.json') .
                                '.lock';

        // Check if composer.lock file exists
        if (!file_exists($composerLockFilename)) {
            throw new ComposerException(sprintf('Project is not initialized with Composer, "%s" file doest not exists', $composerLockFilename));
        }

        // Get JSON content of composer.json file
        if (($this->composerLock = json_decode(file_get_contents($composerLockFilename), true)) === false) {
            throw new ComposerException(sprintf('"%s" file of project is corrupted or not readable', $composerLockFilename));
        }

        // Reindex packages
        $this->composerLock['packages'] = array_column($this->composerLock['packages'], null, 'name');

        // Load packages
        $this->packages = array_fill_keys(array_column($this->composerLock['packages'], 'name'), null);
    }

    ///////////////
    /// LOADING ///
    ///////////////

    /**
     * Load package JSON.
     *
     * @param string $packageName
     *
     * @return array
     * @throws \Berlioz\Core\Exception\ComposerException
     */
    private function loadPackageJson(string $packageName): array
    {
        // Get target directories
        $targetDirs = array_column($this->composerLock['packages'], 'target-dir', 'name');

        // Get package directory and composer.json path
        $packageDirectory = dirname($this->composerJsonFilename) .
                            DIRECTORY_SEPARATOR .
                            ($this->composerJson['config']['vendor-dir'] ?? 'vendor') .
                            DIRECTORY_SEPARATOR .
                            str_replace('/', DIRECTORY_SEPARATOR, $packageName) .
                            DIRECTORY_SEPARATOR .
                            str_replace('/', DIRECTORY_SEPARATOR, $targetDirs[$packageName] ?? '');
        $packageDirectory = rtrim($packageDirectory, DIRECTORY_SEPARATOR);
        $composerFile = $packageDirectory . DIRECTORY_SEPARATOR . 'composer.json';

        if (!is_dir($packageDirectory)) {
            throw new ComposerException(sprintf('Unable to find directory of package "%s"', $packageName));
        }

        if (!file_exists($composerFile)) {
            throw new ComposerException(sprintf('Unable to find composer.json file of package "%s"', $packageName));
        }

        return $this->loadJsonFile($composerFile);
    }

    /**
     * Load a JSON file content.
     *
     * @param string $jsonFile JSON file
     *
     * @return array
     * @throws \Berlioz\Core\Exception\ComposerException
     */
    private function loadJsonFile(string $jsonFile): array
    {
        if (!file_exists($jsonFile)) {
            throw new ComposerException(sprintf('Unable to find "%s" JSON file', $jsonFile));
        }

        // Get JSON content of composer.json file
        if (($json = json_decode(file_get_contents($jsonFile), true)) === false) {
            throw new ComposerException(sprintf('"%s" JSON file is corrupted or not readable', $jsonFile));
        }

        return $json;
    }

    ///////////////
    /// GETTERS ///
    ///////////////

    /**
     * Get project composer.
     *
     * @return array
     */
    public function getProjectComposer(): array
    {
        return $this->composerJson;
    }

    /**
     * Get packages name.
     *
     * @return array
     */
    public function getPackagesName(): array
    {
        return array_keys($this->packages);
    }

    /**
     * Get package.
     *
     * @param string $name
     *
     * @return array
     * @throws \Berlioz\Core\Exception\ComposerException
     */
    public function getPackage(string $name): array
    {
        if (!array_key_exists($name, $this->packages)) {
            throw new ComposerException(sprintf('Unable to found "%s" composer package', $name));
        }

        if (is_null($this->packages[$name])) {
            $this->packages[$name] = $this->loadPackageJson($name);
        }

        return $this->packages[$name];
    }

    /**
     * Get package version installed.
     *
     * @param string $name
     *
     * @return string|null|false
     */
    public function getPackageVersion(string $name)
    {
        if (array_key_exists($name, $this->composerLock['packages'])) {
            return $this->composerLock['packages'][$name]['version'] ?? null;
        }

        return false;
    }

    /**
     * Get packages.
     *
     * @return array
     * @throws \Berlioz\Core\Exception\ComposerException
     */
    public function getPackages(): array
    {
        foreach ($this->packages as $name => &$package) {
            if (is_null($package)) {
                $package = $this->loadPackageJson($name);
            }
        }

        return $this->packages;
    }
}