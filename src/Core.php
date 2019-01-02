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

use Berlioz\Core\Cache\CacheManager;
use Berlioz\Core\Directories\DefaultDirectories;
use Berlioz\Core\Exception\BerliozException;
use Berlioz\Core\Exception\CacheException;
use Berlioz\Core\Exception\ConfigException;
use Berlioz\Core\Exception\ContainerException;
use Berlioz\Core\Exception\PackageException;
use Berlioz\Core\Package\PackageInterface;
use Berlioz\ServiceContainer\Service;
use Berlioz\ServiceContainer\ServiceContainer;
use Berlioz\ServiceContainer\ServiceContainerAwareInterface;
use Berlioz\ServiceContainer\ServiceContainerAwareTrait;
use Psr\SimpleCache\CacheInterface;

/**
 * Class Core.
 *
 * @package Berlioz\Core
 */
class Core implements ServiceContainerAwareInterface, \Serializable
{
    use ServiceContainerAwareTrait;
    /** @var bool Initialized? */
    private $initialized = false;
    /** @var bool Loaded from cache? */
    private $loadedFromCache = false;
    /** @var \Berlioz\Core\Composer */
    private $composer;
    /** @var \Psr\SimpleCache\CacheInterface|null */
    protected $cache;
    /** @var \Berlioz\Core\Debug */
    protected $debug;
    /** @var \Berlioz\Core\Config Configuration */
    protected $config;
    /** @var \Berlioz\Core\Directories\DefaultDirectories DefaultDirectories */
    protected $directories;
    /** @var \Berlioz\Core\Package\PackageInterface[] Packages */
    protected $packages = [];
    /** @var string Locale */
    protected $locale;
    /** @var array Terminate callbacks */
    protected $terminateCallbacks = [];

    /**
     * Core constructor.
     *
     * @param \Berlioz\Core\Directories\DefaultDirectories|null $directories
     *
     * @throws \Berlioz\Core\Exception\BerliozException
     * @throws \Berlioz\ServiceContainer\Exception\ContainerException
     */
    public function __construct(?DefaultDirectories $directories = null)
    {
        // Debug
        if ($_SERVER['REQUEST_TIME_FLOAT']) {
            $this->getDebug()
                 ->getTimeLine()
                 ->addActivity((new Debug\Activity('PHP initialization', 'Berlioz'))
                                   ->start($_SERVER['REQUEST_TIME_FLOAT'])
                                   ->end());
        }

        // Debug
        $this->getDebug()->getTimeLine()->addActivity($berliozActivity = (new Debug\Activity('Start', 'Berlioz'))->start());

        $this->directories = $directories;
        $this->cache = new CacheManager($this->getDirectories());

        // Load from cache
        if (!is_null($this->cache)) {
            $cacheActivity = (new Debug\Activity('Cache', 'Berlioz'))->start();

            try {
                if (!empty($coreCached = $this->cache->get('berlioz-core'))) {
                    $this->fromCache($coreCached);
                }
            } catch (\Psr\SimpleCache\CacheException $e) {
            }

            // Add callback to save cache
            if (!$this->loadedFromCache) {
                $this->onTerminate(function () {
                    if (!is_null($this->cache)) {
                        try {
                            // Save to cache
                            if (!is_null($this->cache)) {
                                $this->cache->set('berlioz-core', $this->toCache());
                            }
                        } catch (\Psr\SimpleCache\CacheException $e) {
                            throw new CacheException('Cache error', 0, $e);
                        }
                    }
                });
            }

            $this->getDebug()->getTimeLine()->addActivity($cacheActivity->end());
        }

        $this->init();

        $berliozActivity->end();
    }

    /**
     * Core destructor.
     *
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function __destruct()
    {
        $coreActivity = (new Debug\Activity('Core terminate', 'Berlioz'))->start();

        foreach ($this->terminateCallbacks as $terminateCallback) {
            $terminateCallback($this);
        }

        $this->getDebug()->getTimeLine()->addActivity($coreActivity->end());

        // Save debug report if debug enabled
        if ($this->getDebug()->isEnabled()) {
            try {
                $this->getDebug()->saveReport();
            } catch (\Throwable $e) {
            }
        }
    }

    /**
     * On terminate.
     *
     * @param callable $callback
     *
     * @return static
     */
    public function onTerminate(callable $callback): Core
    {
        $this->terminateCallbacks[] = $callback;

        return $this;
    }

    /**
     * Initialization.
     *
     * @throws \Berlioz\Core\Exception\BerliozException
     * @throws \Berlioz\ServiceContainer\Exception\ContainerException
     */
    private function init()
    {
        if ($this->initialized) {
            return;
        }

        $this->initialized = true;

        // Add default services to container
        $this->getServiceContainer()->add(new Service($this, 'berlioz'));
        $this->getServiceContainer()->add(new Service($this->getConfig(), 'config'));

        // Packages
        $this->registerPackages();

        // Packages
        $this->initPackages();
    }

    /////////////
    /// CACHE ///
    /////////////

    /**
     * Get cache manager.
     *
     * @return \Psr\SimpleCache\CacheInterface|null
     */
    public function getCacheManager(): ?CacheInterface
    {
        return $this->cache;
    }

    /**
     * @inheritdoc
     */
    protected function toCache(): array
    {
        return ['config'           => $this->config,
                'locale'           => $this->locale,
                'directories'      => $this->directories,
                'serviceContainer' => $this->serviceContainer,
                'packages'         => $this->packages];
    }

    /**
     * @inheritdoc
     */
    protected function fromCache(array $cache)
    {
        $this->loadedFromCache = true;
        $this->config = $cache['config'];
        $this->locale = $cache['locale'];
        $this->directories = $cache['directories'];
        $this->serviceContainer = $cache['serviceContainer'];
        $this->packages = $cache['packages'];

        /** @var \Berlioz\Core\Package\PackageInterface $package */
        foreach ($this->packages as $package) {
            // CoreAwareInterface?
            if ($package instanceof CoreAwareInterface) {
                $package->setCore($this);
            }
        }
    }

    /////////////////////
    /// SERIALIZATION ///
    /////////////////////

    /**
     * @inheritdoc
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function serialize()
    {
        throw new BerliozException(sprintf('Serialization of class "%s" not allowed', __CLASS__));
    }

    /**
     * @inheritdoc
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function unserialize($serialized)
    {
        throw new BerliozException(sprintf('Serialization of class "%s" not allowed', __CLASS__));
    }

    //////////////
    /// DEBUG ///
    //////////////

    /**
     * Get debug manager.
     *
     * @return \Berlioz\Core\Debug
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function getDebug(): Debug
    {
        if (!is_null($this->debug)) {
            return $this->debug;
        }

        $this->debug = new Debug($this);

        return $this->debug;
    }

    //////////////
    /// CONFIG ///
    //////////////

    /**
     * Get configuration.
     *
     * @return \Berlioz\Core\Config
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function getConfig(): ?Config
    {
        if (!is_null($this->config)) {
            return $this->config;
        }

        try {
            $configActivity = (new Debug\Activity('Config (initialization)', 'Berlioz'))->start();

            // Create configuration (from default configuration)
            $config = new Config(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'resources', 'config.default.json']), true);

            // Init default configuration
            //$config->setVariable('berlioz.directories.working', $this->getDirectories()->getWorkingDir());
            $config->setVariable('berlioz.directories.app', $this->getDirectories()->getAppDir());
            $config->setVariable('berlioz.directories.config', $this->getDirectories()->getConfigDir());
            $config->setVariable('berlioz.directories.cache', $this->getDirectories()->getCacheDir());
            $config->setVariable('berlioz.directories.log', $this->getDirectories()->getLogDir());
            $config->setVariable('berlioz.directories.debug', $this->getDirectories()->getDebugDir());
            $config->setVariable('directory_separator', DIRECTORY_SEPARATOR);

            // Search website configs and add
            foreach (glob($this->getDirectories()->getConfigDir() . DIRECTORY_SEPARATOR . '*.json') as $configFile) {
                $config->extendsJson($configFile, true, false);
            }

            // Set config to parent
            $this->config = $config;

            $this->getDebug()->getTimeLine()->addActivity($configActivity->end());

            return $this->config;
        } catch (BerliozException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new ConfigException('Configuration error', 0, $e);
        }
    }

    /////////////////////////
    /// SERVICE CONTAINER ///
    /////////////////////////


    /**
     * Get service container.
     *
     * @return \Berlioz\ServiceContainer\ServiceContainer
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function getServiceContainer(): ServiceContainer
    {
        if (!is_null($this->serviceContainer)) {
            return $this->serviceContainer;
        }

        try {
            $serviceContainerActivity = (new Debug\Activity('Service container (initialization)', 'Berlioz'))->start();

            $this->serviceContainer = new ServiceContainer;
            $servicesConfig = $this->getConfig()->get('services', []);

            // Add services from configuration
            foreach ($servicesConfig as $serviceAlias => $serviceConfig) {
                if (is_string($serviceConfig)) {
                    $this->serviceContainer->add(new Service($serviceConfig));
                    continue;
                }

                if (is_array($serviceConfig)) {
                    if (empty($serviceConfig['class'])) {
                        throw new ContainerException(sprintf('Missing class in configuration of service key "%s"', $serviceAlias));
                    }

                    $service = new Service($serviceConfig['class'], !is_numeric($serviceAlias) ? $serviceAlias : null);

                    if (!empty($serviceConfig['factory'])) {
                        $service->setFactory($serviceConfig['factory']);
                    }

                    // Arguments
                    $service->addArguments($serviceConfig['arguments'] ?? []);

                    // Calls
                    foreach ($serviceConfig['calls'] ?? [] as $call) {
                        $service->addCall($call['method'], $call['arguments'] ?? []);
                    }

                    $this->serviceContainer->add($service);
                }
            }

            $this->getDebug()->getTimeLine()->addActivity($serviceContainerActivity->end());

            return $this->serviceContainer;
        } catch (\Berlioz\ServiceContainer\Exception\ContainerException $e) {
            throw new ContainerException('Service container error', 0, $e);
        } catch (\Berlioz\Config\Exception\ConfigException $e) {
            throw new ConfigException('Configuration error', 0, $e);
        }
    }

    ////////////////
    /// COMPOSER ///
    ////////////////

    /**
     * Get composer.
     *
     * @return \Berlioz\Core\Composer
     */
    public function getComposer(): Composer
    {
        if (is_null($this->composer)) {
            $this->composer = new Composer($this);
        }

        return $this->composer;
    }

    ////////////////
    /// PACKAGES ///
    ////////////////

    /**
     * Register packages.
     *
     * @return void
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    protected function registerPackages()
    {
        if ($this->loadedFromCache) {
            return;
        }

        $packagesActivity = (new Debug\Activity('Packages (registration)', 'Berlioz'))->start();

        // Get packages from PHP file
        try {
            // Get packages from config
            $packagesClass = $this->getConfig()->get('packages', []);

            // Get packages from composer
            foreach ($this->getComposer()->getPackages() as $composerPackage) {
                $composerPackageJson = $this->getComposer()->getPackage($composerPackage);

                if (!isset($composerPackageJson['config']['berlioz']['package'])) {
                    continue;
                }

                $packagesClass[] = (string) $composerPackageJson['config']['berlioz']['package'];
            }
            $packagesClass = array_unique($packagesClass);

            // Load default configuration of packages
            foreach ($packagesClass as $packageClass) {
                if (!is_a($packageClass, PackageInterface::class, true)) {
                    if (!class_exists($packageClass)) {
                        throw new PackageException(sprintf('Package class "%s" does not exists', $packageClass));
                    } else {
                        throw new PackageException(sprintf('Package class "%s" must implements "%s" interface', $packageClass, PackageInterface::class));
                    }
                }

                // Create instance of package and call register method
                /** @var \Berlioz\Core\Package\PackageInterface $package */
                $package = $this->getServiceContainer()
                                ->getInstantiator()
                                ->newInstanceOf($packageClass);
                $package->setCore($this);
                $package->register();

                // Add package
                $this->packages[ltrim($packageClass, '\\')] = $package;
            }
        } catch (PackageException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new PackageException('Error during packages loading', 0, $e);
        } finally {
            $this->getDebug()->getTimeLine()->addActivity($packagesActivity->end());
        }
    }

    /**
     * Init packages.
     *
     * @throws \Berlioz\Core\Exception\PackageException
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    protected function initPackages()
    {
        $packagesActivity = (new Debug\Activity('Packages (initialization)', 'Berlioz'))->start();

        foreach ($this->packages as $packageClass => $package) {
            try {
                // Init package
                $package->init();
            } catch (\Throwable $e) {
                throw new PackageException(sprintf('Error during initialization of package: "%s"', $packageClass), 0, $e);
            }
        }

        $this->getDebug()->getTimeLine()->addActivity($packagesActivity->end());
    }

    /**
     * Get package.
     *
     * @param string $class Class name of package
     *
     * @return \Berlioz\Core\Package\PackageInterface|null
     */
    public function getPackage(string $class): ?PackageInterface
    {
        return $this->packages[$class] ?? null;
    }

    //////////////
    /// LOCALE ///
    //////////////

    /**
     * Get locale.
     *
     * @return string
     * @see \Locale
     */
    public function getLocale(): string
    {
        return $this->locale ?: \Locale::getDefault();
    }

    /**
     * Set locale.
     *
     * @param string $locale
     *
     * @return \Berlioz\Core\Core
     * @throws \Berlioz\Core\Exception\BerliozException
     * @see \Locale
     */
    public function setLocale(string $locale): Core
    {
        if (\Locale::setDefault($locale) !== true) {
            throw new BerliozException(sprintf('Locale "%s" is not correct', $locale));
        }
        $this->locale = $locale;

        return $this;
    }

    ///////////////////
    /// DIRECTORIES ///
    ///////////////////

    /**
     * Get directories.
     *
     * @return \Berlioz\Core\Directories\DefaultDirectories
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function getDirectories(): DefaultDirectories
    {
        if (is_null($this->directories)) {
            // Debug
            $this->getDebug()->getTimeLine()->addActivity($berliozActivity = (new Debug\Activity('Directories', 'Berlioz'))->start());

            $this->directories = new DefaultDirectories();

            $berliozActivity->end();
        }

        return $this->directories;
    }
}