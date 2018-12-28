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

use Berlioz\Core\Exception\BerliozException;

/**
 * Class Composer.
 *
 * @package Berlioz\Core
 */
class Composer implements \Serializable, CoreAwareInterface
{
    use CoreAwareTrait;
    /** @var array Composer lock */
    private $composerLock;
    /** @var array Packages */
    private $packages;
    /** @var array Berlioz packages */
    private $berliozPackages;

    /**
     * Composer constructor.
     *
     * @param \Berlioz\Core\Core $core
     */
    public function __construct(Core $core)
    {
        $this->setCore($core);
    }

    /////////////////////
    /// SERIALIZATION ///
    /////////////////////

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        return serialize(['composerLock' => $this->composerLock]);
    }

    /**
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        $unserialized = unserialize($serialized);

        $this->composerLock = $unserialized['composerLock'] ?? null;
    }

    ///////////////
    /// LOADING ///
    ///////////////

    /**
     * Load composer.lock file content.
     *
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    private function load()
    {
        if (!$this->isLoaded()) {
            $composerLockFile = implode(DIRECTORY_SEPARATOR, [$this->getCore()->getDirectories()->getAppDir(), 'composer.lock']);

            // Check if composer.lock file is present
            if (!file_exists($composerLockFile)) {
                throw new BerliozException('Project is not initialized with Composer');
            }

            // Get JSON content of composer.lock file
            if (($this->composerLock = json_decode(file_get_contents($composerLockFile), true)) === false) {
                throw new BerliozException('composer.lock file of project is corrupted or not readable');
            }

            $this->packages = array_column($this->composerLock['packages'], null, 'name');
        }
    }

    /**
     * Is loaded?
     *
     * @return bool
     */
    private function isLoaded(): bool
    {
        return !is_null($this->composerLock);
    }

    ////////////////
    /// PACKAGES ///
    ////////////////

    /**
     * Get packages.
     *
     * @return array
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function getPackages(): array
    {
        $this->load();

        return array_column($this->packages, 'name');
    }

    /**
     * Get package.
     *
     * @param string $name
     *
     * @return array
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function getPackage(string $name): array
    {
        $this->load();

        if (!isset($this->packages[$name])) {
            throw new BerliozException(sprintf('Unable to found "%s" composer package', $name));
        }
        $package = $this->packages[$name];

        // Get package directory and composer.json path
        $packageDirectory = $this->getCore()->getDirectories()->getVendorDir() .
                            DIRECTORY_SEPARATOR .
                            str_replace('/', DIRECTORY_SEPARATOR, $name) .
                            DIRECTORY_SEPARATOR .
                            str_replace('/', DIRECTORY_SEPARATOR, $package['target-dir'] ?? '');
        $packageDirectory = trim($packageDirectory, DIRECTORY_SEPARATOR);
        $composerFile = $packageDirectory . DIRECTORY_SEPARATOR . 'composer.json';

        if (!is_dir($packageDirectory)) {
            throw new BerliozException(sprintf('Unable to find directory of package "%s"', $name));
        }

        if (!file_exists($composerFile)) {
            throw new BerliozException(sprintf('Unable to find composer.json file of package "%s"', $name));
        }

        // Get JSON content of composer.lock file
        if (($composerJson = json_decode(file_get_contents($composerFile), true)) === false) {
            throw new BerliozException('composer.lock file of project is corrupted or not readable');
        }

        return $composerJson;
    }

    /**
     * Get Berlioz packages.
     *
     * @return array
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function getBerliozPackages(): array
    {
        $this->load();

        $berliozPackages =
            array_filter($this->packages,
                function ($value) {
                    if (!isset($value['type'])) {
                        return false;
                    }

                    if ($value['type'] == "berlioz-package") {
                        return true;
                    }

                    if (isset($value['config']['berlioz'])) {
                        return true;
                    }

                    return false;
                });

        return array_column($berliozPackages, 'name');
    }
}