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

namespace Berlioz\Core\Package;

use Berlioz\Config\ConfigInterface;
use Berlioz\Config\ExtendedJsonConfig;
use Berlioz\Core\Core;
use Berlioz\Core\CoreAwareInterface;
use Berlioz\Core\CoreAwareTrait;
use Berlioz\Core\Debug;
use Berlioz\Core\Exception\PackageException;
use Berlioz\ServiceContainer\Instantiator;
use Serializable;
use Throwable;

/**
 * Class PackageSet.
 *
 * @package Berlioz\Core\Package
 */
class PackageSet implements Serializable, CoreAwareInterface
{
    use CoreAwareTrait;
    /** @var string[] Packages */
    private $packagesClasses = [];
    /** @var \Berlioz\Core\Package\PackageInterface[] Packages instances */
    private $packages = [];
    /** @var bool Packages configured? */
    private $configured = false;
    /** @var bool Packages registered? */
    private $registered = false;
    /** @var bool Packages initialized? */
    private $initialized = false;

    /**
     * PackageSet constructor.
     *
     * @param \Berlioz\Core\Core $core
     */
    public function __construct(Core $core)
    {
        $this->setCore($core);
    }

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
            'configured' => $this->configured,
            'registered' => $this->registered,
            'initialized' => $this->initialized,
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
                'configured' => $this->configured,
                'registered' => $this->registered,
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
        $this->configured = $unserialized['configured'] ?? [];
        $this->registered = $unserialized['registered'] ?? [];
    }

    //////////////////////////
    /// CoreAwareInterface ///
    //////////////////////////

    /**
     * @inheritdoc
     */
    public function setCore(Core $core)
    {
        $this->core = $core;

        /** @var \Berlioz\Core\Package\PackageInterface $package */
        foreach ($this->packages as $package) {
            $package->setCore($core);
        }
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
     * @throws \Berlioz\Core\Exception\PackageException
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
     * @throws \Berlioz\Core\Exception\PackageException
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
     * @param \Berlioz\Config\ConfigInterface ...$config
     *
     * @return static
     * @throws \Berlioz\Core\Exception\PackageException
     */
    public function addPackagesFromConfig(ConfigInterface ...$config): PackageSet
    {
        try {
            foreach ($config as $aConfig) {
                $packages = $aConfig->get('packages', []);

                if (!is_array($packages)) {
                    throw new PackageException('"packages" configuration entry must be an array of classes');
                }

                $this->addPackages($packages);
            }

            return $this;
        } catch (Throwable $e) {
            throw new PackageException('Unable to load packages from configuration', 0, $e);
        }
    }

    /**
     * Add packages from composer.
     *
     * @return static
     * @throws \Berlioz\Core\Exception\PackageException
     */
    public function addPackagesFromComposer(): PackageSet
    {
        try {
            $composerPackages = $this->getCore()->getComposer()->getPackages();

            // Load default configuration of packages
            foreach ($composerPackages as $composerPackage) {
                if (empty($composerPackage['config']['berlioz']['package'])) {
                    continue;
                }

                $this->addPackage($composerPackage['config']['berlioz']['package']);
            }

            return $this;
        } catch (Throwable $e) {
            throw new PackageException('Unable to load packages from composer', 0, $e);
        }
    }

    //////////////////////
    /// PACKAGES STEPS ///
    //////////////////////

    /**
     * Config of packages.
     *
     * @return static
     * @throws \Berlioz\Core\Exception\PackageException
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function config(): PackageSet
    {
        if ($this->configured) {
            throw new PackageException('Packages already configured');
        }

        // Debug
        $packagesActivity = (new Debug\Activity('Packages (configuration)', 'Berlioz'))->start();

        try {
            /** * @var \Berlioz\Core\Package\PackageInterface $package */
            foreach ($this->packagesClasses as $package) {
                $config = $package::config();

                // No configuration
                if (null === $config) {
                    continue;
                }

                // ConfigInterface
                if ($config instanceof ConfigInterface) {
                    $this->getCore()->getConfig()->merge($config);
                    continue;
                }

                // Array
                if (is_array($config)) {
                    $this->getCore()->getConfig()->merge(new ExtendedJsonConfig(json_encode($config)));
                    continue;
                }

                // String?
                if (is_array($config)) {
                    $this->getCore()->getConfig()->merge(new ExtendedJsonConfig($config, true));
                    continue;
                }

                throw new PackageException(
                    'Configuration of package must be a ConfigInterface, an array, a filename, or null'
                );
            }
        } catch (Throwable $e) {
            throw new PackageException('Error during registration of packages', 0, $e);
        } finally {
            $this->getCore()->getDebug()->getTimeLine()->addActivity($packagesActivity->end());
        }

        $this->configured = true;

        return $this;
    }

    /**
     * Register packages.
     *
     * @return static
     * @throws \Berlioz\Core\Exception\PackageException
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function register(): PackageSet
    {
        if ($this->registered) {
            throw new PackageException('Packages already registered');
        }

        // Debug
        $packagesActivity = (new Debug\Activity('Packages (registration)', 'Berlioz'))->start();

        try {
            /** * @var \Berlioz\Core\Package\PackageInterface $package */
            foreach ($this->packagesClasses as $package) {
                $package::register($this->getCore());
                $this->registered[] = $package;
            }
        } catch (Throwable $e) {
            throw new PackageException('Error during registration of packages', 0, $e);
        } finally {
            $this->getCore()->getDebug()->getTimeLine()->addActivity($packagesActivity->end());
        }

        $this->registered = true;

        return $this;
    }

    /**
     * Instantiate packages.
     *
     * @param \Berlioz\ServiceContainer\Instantiator $instantiator
     *
     * @return static
     * @throws \Berlioz\Core\Exception\PackageException
     */
    protected function instantiate(Instantiator $instantiator): PackageSet
    {
        try {
            foreach ($this->packagesClasses as $packageClass) {
                if (array_key_exists($packageClass, $this->packages)) {
                    continue;
                }

                /** @var \Berlioz\Core\Package\PackageInterface $package */
                $this->packages[$packageClass] =
                $package = $instantiator->newInstanceOf($packageClass);

                // Set Core to the package
                if (null === $package->getCore()) {
                    $package->setCore($this->getCore());
                }
            }

            return $this;
        } catch (Throwable $e) {
            throw new PackageException('Unable to instantiate packages', 0, $e);
        }
    }

    /**
     * Init packages.
     *
     * @return static
     * @throws \Berlioz\Core\Exception\PackageException
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function init(): PackageSet
    {
        if ($this->initialized) {
            throw new PackageException('Packages already initialized');
        }

        // Debug
        $packagesActivity = (new Debug\Activity('Packages (initialization)', 'Berlioz'))->start();

        try {
            $this->instantiate($this->getCore()->getServiceContainer()->getInstantiator());

            foreach ($this->packages as $class => $package) {
                $package->init();
                $this->initialized[] = $class;
            }
        } catch (Throwable $e) {
            throw new PackageException('Error during initialization of packages', 0, $e);
        } finally {
            $this->getCore()->getDebug()->getTimeLine()->addActivity($packagesActivity->end());
        }

        $this->initialized = true;

        return $this;
    }
}