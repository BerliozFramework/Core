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

use Berlioz\Config\ConfigInterface;
use Berlioz\Config\Exception\ConfigException;
use Berlioz\Config\ExtendedJsonConfig;
use Berlioz\Core\Cache\CacheManager;
use Berlioz\Core\Directories\DefaultDirectories;
use Berlioz\Core\Directories\DirectoriesInterface;
use Berlioz\Core\Exception\BerliozException;
use Berlioz\Core\Exception\ContainerException;
use Berlioz\Core\Package\PackageSet;
use Berlioz\ServiceContainer\Service;
use Berlioz\ServiceContainer\ServiceContainer;
use Berlioz\ServiceContainer\ServiceContainerAwareInterface;
use Berlioz\ServiceContainer\ServiceContainerAwareTrait;
use Locale;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Serializable;
use Throwable;

/**
 * Class Core.
 *
 * @package Berlioz\Core
 */
class Core implements ServiceContainerAwareInterface, Serializable
{
    use ServiceContainerAwareTrait;
    /** @var \Berlioz\Core\Composer */
    protected $composer;
    /** @var \Psr\SimpleCache\CacheInterface|null */
    protected $cache;
    /** @var \Berlioz\Core\Debug */
    protected $debug;
    /** @var \Berlioz\Config\ConfigInterface Configuration */
    protected $config;
    /** @var \Berlioz\Core\Directories\DirectoriesInterface Directories */
    protected $directories;
    /** @var \Berlioz\Core\Package\PackageSet Packages */
    protected $packages;
    /** @var string Locale */
    protected $locale;
    /** @var array Terminate callbacks */
    protected $terminateCallbacks = [];

    /**
     * Core constructor.
     *
     * @param \Berlioz\Core\Directories\DirectoriesInterface|null $directories
     * @param \Psr\SimpleCache\CacheInterface|bool $cache
     *
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function __construct(?DirectoriesInterface $directories = null, $cache = true)
    {
        // Debug
        if ($_SERVER['REQUEST_TIME_FLOAT']) {
            $this
                ->getDebug()
                ->newActivity('PHP initialization', 'Berlioz')
                ->start($_SERVER['REQUEST_TIME_FLOAT'])
                ->end();
        }

        // Debug
        $berliozActivity = $this->getDebug()->newActivity('Start', 'Berlioz')->start();

        // Directories
        $this->directories = $directories;

        // Cache manager
        if (is_bool($cache) && $cache === true) {
            $this->cache = new CacheManager($this->getDirectories());
        }
        if ($cache instanceof CacheInterface) {
            $this->cache = $cache;
        }

        // Init framework
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
        $this->terminate();
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
     * Is cache enabled?
     *
     * @return bool
     */
    public function isCacheEnabled(): bool
    {
        return $this->cache instanceof CacheInterface;
    }

    /**
     * Load Core from cache.
     *
     * @return bool Returns TRUE if Core loaded from cache
     * @throws \Berlioz\Core\Exception\BerliozException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function loadCache(): bool
    {
        $cacheActivity = $this->getDebug()->newActivity('Cache (load)', 'Berlioz')->start();

        try {
            // No cache manager?
            if (!$this->isCacheEnabled()) {
                return false;
            }

            // No data in cache?
            if (empty($cache = $this->getCacheManager()->get('berlioz-core'))) {
                return false;
            }

            $this->config = $cache['config'] ?? null;
            $this->locale = $cache['locale'] ?? null;
            $this->serviceContainer = $cache['serviceContainer'] ?? null;
            $this->packages = $cache['packages'] ?? null;
            $this->getPackages()->setCore($this);

            return true;
        } finally {
            $cacheActivity->end();
        }
    }

    /**
     * Save Core to cache.
     *
     * @return bool
     * @throws \Berlioz\Core\Exception\BerliozException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function saveCache(): bool
    {
        $cacheActivity = $this->getDebug()->newActivity('Cache (saving)', 'Berlioz')->start();

        try {
            // No cache manager?
            if (!$this->isCacheEnabled()) {
                return false;
            }

            return
                $this->getCacheManager()
                    ->set(
                        'berlioz-core',
                        [
                            'config' => $this->config,
                            'locale' => $this->locale,
                            'serviceContainer' => $this->serviceContainer,
                            'packages' => $this->packages,
                        ]
                    );
        } finally {
            $cacheActivity->end();
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
        throw new BerliozException(sprintf('Serialization of class "%s" not allowed', static::class));
    }

    /**
     * @inheritdoc
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function unserialize($serialized)
    {
        throw new BerliozException(sprintf('Serialization of class "%s" not allowed', static::class));
    }

    ////////////////////////////////////
    /// INITIALIZATION / TERMINATION ///
    ////////////////////////////////////

    /**
     * Initialization.
     *
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    protected function init()
    {
        try {
            // Load from cache
            try {
                $fromCache = $this->loadCache();
            } catch (Throwable $e) {
                $fromCache = false;
            } catch (InvalidArgumentException $e) {
                $fromCache = false;
            }

            // Not cached?
            if (!$fromCache) {
                // Init default configuration
                $this->initConfig();

                // Init service container
                $this->initServiceContainer();

                // Register packages (configuration & services)
                $this->getPackages()->register();

                // Event to save cache
                $this->onTerminate(
                    function () {
                        if ($this->isCacheEnabled()) {
                            $this->saveCache();
                        }
                    }
                );
            }

            // Add default services to container
            $this->getServiceContainer()->add(new Service($this, 'berlioz'));
            $this->getServiceContainer()->add(new Service($this->getConfig(), 'config'));

            // Packages
            $this->getPackages()->init();
        } catch (Throwable $e) {
            throw new BerliozException('Initialization error', 0, $e);
        }
    }

    /**
     * Termination.
     *
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    private function terminate()
    {
        $coreActivity = $this->getDebug()->newActivity('Core terminate', 'Berlioz')->start();

        foreach ($this->terminateCallbacks as $terminateCallback) {
            $terminateCallback($this);
        }

        $coreActivity->end();

        // Save debug report if debug enabled
        if ($this->getDebug()->isEnabled()) {
            try {
                $this->getDebug()->saveReport();
            } catch (Throwable $e) {
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
        if (null === $this->debug) {
            $this->debug = new Debug($this);
        }

        return $this->debug;
    }

    //////////////
    /// CONFIG ///
    //////////////

    /**
     * Init configuration.
     *
     * @return \Berlioz\Config\ConfigInterface
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    protected function initConfig(): ConfigInterface
    {
        $configActivity = $this->getDebug()->newActivity('Configuration', 'Berlioz')->start();

        try {
            // Create configuration (from default configuration)
            $configFile = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'resources', 'config.default.json']);
            $this->config = new ExtendedJsonConfig($configFile, true);

            // Init default configuration
            $this->config->setVariables(
                [
                    'berlioz.directories.working',
                    $this->getDirectories()->getWorkingDir(),
                    'berlioz.directories.app' => $this->getDirectories()->getAppDir(),
                    'berlioz.directories.config' => $this->getDirectories()->getConfigDir(),
                    'berlioz.directories.cache' => $this->getDirectories()->getCacheDir(),
                    'berlioz.directories.log' => $this->getDirectories()->getLogDir(),
                    'berlioz.directories.debug' => $this->getDirectories()->getDebugDir(),
                    'directory_separator' => DIRECTORY_SEPARATOR,
                ]
            );

            // Init user configuration
            $userConfig = new ExtendedJsonConfig('{}');

            // Search user configs and add
            foreach (glob($this->getDirectories()->getConfigDir() . DIRECTORY_SEPARATOR . '*.json') as $configFile) {
                $userConfig->merge(new ExtendedJsonConfig($configFile, true));
            }

            // Add packages from Composer
            $this->getPackages()->addPackagesFromComposer();
            // Add packages from configurations
            $this->getPackages()->addPackagesFromConfig($this->config, $userConfig);

            // Get configuration of packages
            $this->getPackages()->config();

            // Merge user configuration
            $this->config->merge($userConfig);
        } catch (Throwable $e) {
            throw new BerliozException('Configuration error', 0, $e);
        } finally {
            $configActivity->end();
        }

        return $this->config;
    }

    /**
     * Get configuration.
     *
     * @return \Berlioz\Config\ConfigInterface|null
     */
    public function getConfig(): ?ConfigInterface
    {
        return $this->config;
    }

    /////////////////////////
    /// SERVICE CONTAINER ///
    /////////////////////////

    /**
     * Init service container.
     *
     * @return \Berlioz\ServiceContainer\ServiceContainer
     * @throws \Berlioz\Core\Exception\BerliozException
     * @throws \Berlioz\Core\Exception\ContainerException
     */
    public function initServiceContainer()
    {
        $containerActivity = $this->getDebug()->newActivity('Service container (initialization)', 'Berlioz')->start();

        try {
            $this->serviceContainer = new ServiceContainer();
            $servicesConfig = $this->getConfig()->get('services', []);

            // Add services from configuration
            foreach ($servicesConfig as $serviceAlias => $serviceConfig) {
                // If service config is a string, so a class
                if (is_string($serviceConfig)) {
                    $this->serviceContainer->add(new Service($serviceConfig));
                    continue;
                }

                // If not an array, continue, because not a class and not a config
                if (!is_array($serviceConfig)) {
                    continue;
                }

                if (empty($serviceConfig['class'])) {
                    throw new ContainerException(
                        sprintf('Missing class in configuration of service key "%s"', $serviceAlias)
                    );
                }

                // Create service object
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

            return $this->serviceContainer;
        } catch (\Berlioz\ServiceContainer\Exception\ContainerException $e) {
            throw new ContainerException('Service container error', 0, $e);
        } catch (ConfigException $e) {
            throw new BerliozException('Configuration error', 0, $e);
        } finally {
            $containerActivity->end();
        }
    }

    /**
     * Get service container.
     *
     * @return \Berlioz\ServiceContainer\ServiceContainer
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function getServiceContainer(): ServiceContainer
    {
        if (null === $this->serviceContainer) {
            $this->initServiceContainer();
        }

        return $this->serviceContainer;
    }

    ////////////////
    /// COMPOSER ///
    ////////////////

    /**
     * Get composer.
     *
     * @return \Berlioz\Core\Composer
     * @throws \Berlioz\Core\Exception\ComposerException
     */
    public function getComposer(): Composer
    {
        if (null === $this->composer) {
            $this->composer = new Composer(
                $this->getDirectories()->getAppDir() . DIRECTORY_SEPARATOR . 'composer.json'
            );
        }

        return $this->composer;
    }

    ////////////////
    /// PACKAGES ///
    ////////////////

    /**
     * Get packages set.
     *
     * @return \Berlioz\Core\Package\PackageSet
     */
    public function getPackages(): PackageSet
    {
        if (null === $this->packages) {
            $this->packages = new PackageSet($this);
        }

        return $this->packages;
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
        return $this->locale ?: Locale::getDefault();
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
        if (Locale::setDefault($locale) !== true) {
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
     * @return \Berlioz\Core\Directories\DirectoriesInterface
     */
    public function getDirectories(): DirectoriesInterface
    {
        if (null === $this->directories) {
            $this->directories = new DefaultDirectories();
        }

        return $this->directories;
    }
}