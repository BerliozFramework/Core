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

declare(strict_types=1);

namespace Berlioz\Core\Package;

use Berlioz\Config\ConfigInterface;
use Berlioz\Config\Exception\ConfigException;
use Berlioz\Config\ExtendedJsonConfig;
use Berlioz\Core\Composer;
use Berlioz\Core\Exception\ComposerException;
use Berlioz\Core\Exception\PackageException;
use Berlioz\ServiceContainer\Instantiator;
use Serializable;
use Throwable;

/**
 * Class PackageSet.
 *
 * @package Berlioz\Core\Package
 */
class PackageSet implements Serializable
{
    /** @var string[] Packages */
    private $packagesClasses = [];
    /** @var PackageInterface[] Packages instances */
    private $packages = [];

    /**
     * Magic method __debugInfo().
     *
     * @return array
     */
    public function __debugInfo(): array
    {
        return [
            'packagesClasses' => $this->packagesClasses,
            'packages' => $this->packages,
        ];
    }

    ////////////////////
    /// SERIALIZABLE ///
    ////////////////////

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        return serialize(
            [
                'packagesClasses' => $this->packagesClasses,
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        $unserialized = unserialize($serialized);

        $this->packagesClasses = $unserialized['packagesClasses'] ?? [];
    }

    //////////////////////////////
    /// PACKAGES MANIPULATIONS ///
    //////////////////////////////

    /**
     * Get packages classes.
     *
     * @return array
     */
    public function getPackages(): array
    {
        return $this->packagesClasses;
    }

    /**
     * Add a package.
     *
     * @param string $packageClass
     *
     * @return static
     * @throws PackageException
     */
    public function addPackage(string $packageClass): PackageSet
    {
        if (in_array($packageClass, $this->packagesClasses)) {
            return $this;
        }

        if (!is_a($packageClass, PackageInterface::class, true)) {
            throw new PackageException(
                sprintf('Class "%s" must implements "%s" interface', $packageClass, PackageInterface::class)
            );
        }

        $this->packagesClasses[] = $packageClass;

        return $this;
    }

    /**
     * Add packages.
     *
     * @param array $packagesClasses
     *
     * @return static
     * @throws PackageException
     */
    public function addPackages(array $packagesClasses): PackageSet
    {
        foreach ($packagesClasses as $packageClass) {
            $this->addPackage($packageClass);
        }

        return $this;
    }

    /**
     * Add packages from configuration.
     *
     * @param ConfigInterface ...$config
     *
     * @return static
     * @throws PackageException
     * @throws ConfigException
     */
    public function addPackagesFromConfig(ConfigInterface ...$config): PackageSet
    {
        foreach ($config as $aConfig) {
            $packages = $aConfig->get('packages', []);

            if (!is_array($packages)) {
                throw new PackageException('"packages" configuration entry must be an array of classes');
            }

            $this->addPackages($packages);
        }

        return $this;
    }

    /**
     * Add packages from composer.
     *
     * @param Composer $composer
     *
     * @return static
     * @throws ComposerException
     * @throws PackageException
     */
    public function addPackagesFromComposer(Composer $composer): PackageSet
    {
        $composerPackages = $composer->getPackages();

        // Load default configuration of packages
        foreach ($composerPackages as $composerPackage) {
            if (empty($composerPackage['config']['berlioz']['package'])) {
                continue;
            }

            $this->addPackage($composerPackage['config']['berlioz']['package']);
        }

        return $this;
    }

    //////////////////////
    /// PACKAGES STEPS ///
    //////////////////////

    /**
     * Config of packages.
     *
     * @param ConfigInterface $config
     *
     * @return static
     * @throws PackageException
     */
    public function config(ConfigInterface $config): PackageSet
    {
        /** * @var PackageInterface $package */
        foreach ($this->packagesClasses as $package) {
            try {
                $packageConfig = $package::config();

                // No configuration
                if (null === $packageConfig) {
                    continue;
                }

                // ConfigInterface
                if ($packageConfig instanceof ConfigInterface) {
                    $config->merge($packageConfig);
                    continue;
                }

                // Array
                if (is_array($packageConfig)) {
                    $config->merge(new ExtendedJsonConfig(json_encode($packageConfig)));
                    continue;
                }

                // String?
                if (is_string($packageConfig)) {
                    $config->merge(new ExtendedJsonConfig($packageConfig, true));
                    continue;
                }

                throw new PackageException(
                    sprintf(
                        'Configuration of package "%s" must be a ConfigInterface, an array, a filename, or NULL',
                        $package
                    )
                );
            } catch (PackageException $e) {
                throw $e;
            } catch (Throwable $e) {
                throw new PackageException(sprintf('Error during registration of package "%s"', $package), 0, $e);
            }
        }

        return $this;
    }

    /**
     * Register packages.
     *
     * @param Instantiator $instantiator
     *
     * @return static
     * @throws PackageException
     */
    public function register(Instantiator $instantiator): PackageSet
    {
        /** * @var PackageInterface $package */
        foreach ($this->packagesClasses as $package) {
            try {
                $instantiator->invokeMethod($package, 'register');
            } catch (Throwable $e) {
                throw new PackageException(sprintf('Error during registration of package "%s"', $package), 0, $e);
            }
        }

        return $this;
    }

    /**
     * Instantiate packages.
     *
     * @param Instantiator $instantiator
     *
     * @return static
     * @throws PackageException
     */
    protected function instantiate(Instantiator $instantiator): PackageSet
    {
        foreach ($this->packagesClasses as $packageClass) {
            try {
                if (array_key_exists($packageClass, $this->packages)) {
                    continue;
                }

                $this->packages[$packageClass] = $instantiator->newInstanceOf($packageClass);
            } catch (Throwable $e) {
                throw new PackageException(sprintf('Error during instantiation of package "%s"', $packageClass), 0, $e);
            }
        }

        return $this;
    }

    /**
     * Init packages.
     *
     * @param Instantiator $instantiator
     *
     * @return static
     * @throws PackageException
     */
    public function init(Instantiator $instantiator): PackageSet
    {
        $this->instantiate($instantiator);

        foreach ($this->packages as $class => $package) {
            try {
                $instantiator->invokeMethod($package, 'init');
            } catch (Throwable $e) {
                throw new PackageException(sprintf('Error during initialization of package "%s"', $class), 0, $e);
            }
        }

        return $this;
    }
}