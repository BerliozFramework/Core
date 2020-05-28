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

namespace Berlioz\Core;

use Berlioz\Config\ConfigInterface;
use Berlioz\Config\Exception\ConfigException;
use Berlioz\Config\ExtendedJsonConfig;
use Berlioz\Core\Cache\CacheManager;
use Berlioz\Core\Cache\NullCacheManager;
use Berlioz\Core\Directories\DefaultDirectories;
use Berlioz\Core\Directories\DirectoriesInterface;
use Berlioz\Core\Exception\BerliozException;
use Berlioz\Core\Exception\ComposerException;
use Berlioz\Core\Exception\ContainerException;
use Berlioz\Core\Package\PackageSet;
use Berlioz\ServiceContainer\Service;
use Berlioz\ServiceContainer\ServiceContainer;
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
class Core implements Serializable
{
    /** @var Composer */
    protected $composer;
    /** @var CacheInterface */
    protected $cache;
    /** @var ConfigInterface Configuration */
    protected $config;
    /** @var Debug */
    protected $debug;
    /** @var DirectoriesInterface Directories */
    protected $directories;
    /** @var string Locale */
    protected $locale;
    /** @var ServiceContainer Service container */
    protected $serviceContainer;
    /** @var array Terminate callbacks */
    protected $terminateCallbacks = [];
    /** @var PackageSet Packages */
    protected $packages;

    /**
     * Core constructor.
     *
     * @param DirectoriesInterface|null $directories
     * @param CacheInterface|bool $cache
     *
     * @throws BerliozException
     */
    public function __construct(?DirectoriesInterface $directories = null, $cache = true)
    {
        // Debug
        $phpActivity = new Debug\Activity('PHP initialization', 'Berlioz');
        if ($_SERVER['REQUEST_TIME_FLOAT']) {
            $phpActivity->start($_SERVER['REQUEST_TIME_FLOAT'])->end();
        }
        $berliozActivity = (new Debug\Activity('Start', 'Berlioz'))->start();

        // Directories
        $this->directories = $directories ?? new DefaultDirectories();

        // Cache manager
        $this->cache = new NullCacheManager();
        if ($cache === true) {
            $this->cache = new CacheManager($this->getDirectories());
        }
        if ($cache instanceof CacheInterface) {
            $this->cache = $cache;
        }

        // Init framework
        $this->init();

        $this->getDebug()->getTimeLine()->addActivity($phpActivity);
        $this->getDebug()->getTimeLine()->addActivity($berliozActivity->end());
    }

    /**
     * Core destructor.
     *
     * @throws BerliozException
     */
    public function __destruct()
    {
        $this->terminate();
    }

    /////////////////////
    /// SERIALIZATION ///
    /////////////////////

    /**
     * @inheritdoc
     * @throws BerliozException
     */
    public function serialize()
    {
        throw new BerliozException(sprintf('Serialization of class "%s" not allowed', static::class));
    }

    /**
     * @inheritdoc
     * @throws BerliozException
     */
    public function unserialize($serialized)
    {
        throw new BerliozException(sprintf('Serialization of class "%s" not allowed', static::class));
    }

    /////////////
    /// CACHE ///
    /////////////

    /**
     * Get cache manager.
     *
     * @return CacheInterface
     */
    public function getCacheManager(): CacheInterface
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
        return
            $this->cache instanceof CacheInterface &&
            !$this->cache instanceof NullCacheManager;
    }

    /**
     * Load Core from cache.
     *
     * @return bool Returns TRUE if Core loaded from cache
     * @throws BerliozException
     */
    protected function loadCache(): bool
    {
        $cacheActivity = (new Debug\Activity('Cache (load)', 'Berlioz'))->start();

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

            $this->addDefaultServices();

            $this->getDebug()->getTimeLine()->addActivity($cacheActivity->end());

            return true;
        } catch (InvalidArgumentException $e) {
            throw new BerliozException('Cache error', 0, $e);
        }
    }

    /**
     * Save Core to cache.
     *
     * @return bool
     * @throws BerliozException
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
        } catch (InvalidArgumentException $e) {
            throw new BerliozException('Cache error', 0, $e);
        } finally {
            $cacheActivity->end();
        }
    }

    ////////////////////////////////////
    /// INITIALIZATION / TERMINATION ///
    ////////////////////////////////////

    /**
     * Initialization.
     *
     * @throws BerliozException
     */
    protected function init()
    {
        try {
            if (!$this->loadCache()) {
                // Init default configuration
                $this->initConfig();

                // Init service container
                $this->initServiceContainer();

                // Register packages (configuration & services)
                $this->getPackages()->register($this->getServiceContainer()->getInstantiator());

                // Event to save cache
                $this->onTerminate(
                    function () {
                        if ($this->isCacheEnabled()) {
                            $this->saveCache();
                        }
                    }
                );
            }

            // Init packages
            $this->getPackages()->init($this->getServiceContainer()->getInstantiator());
        } catch (BerliozException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new BerliozException('Initialization error', 0, $e);
        }
    }

    /**
     * Termination.
     *
     * @throws BerliozException
     */
    public function terminate()
    {
        $coreActivity = $this->getDebug()->newActivity('Core terminate', 'Berlioz')->start();

        foreach ($this->terminateCallbacks as $callback) {
            $callback($this);
        }

        $coreActivity->end()->setDetail(sprintf('%d callback(s)', count($this->terminateCallbacks)));

        // Save debug report if debug enabled
        if ($this->getDebug()->isEnabled()) {
            try {
                $debugDir = $this->getDirectories()->getDebugDir();
                if (is_dir($debugDir) || mkdir($debugDir, 0777, true)) {
                    file_put_contents(
                        $debugDir . DIRECTORY_SEPARATOR . $this->getDebug()->getUniqid() . '.debug',
                        $this->getDebug()->saveReport()
                    );
                }
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
     * @return Debug
     * @throws BerliozException
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
     * @return ConfigInterface
     * @throws BerliozException
     */
    protected function initConfig(): ConfigInterface
    {
        $configActivity = (new Debug\Activity('Configuration', 'Berlioz'))->start();

        try {
            // Create configuration (from default configuration)
            $configFile = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'resources', 'config.default.json']);
            $this->config = new ExtendedJsonConfig($configFile, true);

            // Init default configuration
            $this->config->setVariables(
                [
                    'berlioz.directories.working' => $this->getDirectories()->getWorkingDir(),
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
            $this->getPackages()->addPackagesFromComposer($this->getComposer());

            // Add packages from configurations
            $this->getPackages()->addPackagesFromConfig($this->config, $userConfig);

            // Get configuration of packages
            $this->getPackages()->config($this->config);

            // Merge user configuration
            $this->config->merge($userConfig);
        } catch (BerliozException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new BerliozException('Configuration error', 0, $e);
        } finally {
            $this->getDebug()->getTimeLine()->addActivity($configActivity->end());
        }

        return $this->config;
    }

    /**
     * Get configuration.
     *
     * @return ConfigInterface|null
     */
    public function getConfig(): ?ConfigInterface
    {
        return $this->config;
    }

    /////////////////////////
    /// SERVICE CONTAINER ///
    /////////////////////////

    /**
     * Get service container.
     *
     * @return ServiceContainer
     */
    public function getServiceContainer(): ServiceContainer
    {
        return $this->serviceContainer;
    }

    /**
     * Init service container.
     *
     * @return ServiceContainer
     * @throws BerliozException
     */
    public function initServiceContainer()
    {
        $containerActivity = $this->getDebug()->newActivity('Service container (initialization)', 'Berlioz')->start();

        try {
            $this->serviceContainer = new ServiceContainer();
            $this->addDefaultServices();
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
     * Add default services.
     *
     * @throws BerliozException
     */
    private function addDefaultServices()
    {
        try {
            // Add default services to container
            $this->getServiceContainer()->add(new Service($this, 'berlioz'));
            $this->getServiceContainer()->add(new Service($this->getConfig(), 'config'));
            $this->getServiceContainer()->add(new Service($this->getDirectories(), 'directories'));
        } catch (Throwable $e) {
            throw new BerliozException('Unable to add default services');
        }
    }

    ////////////////
    /// COMPOSER ///
    ////////////////

    /**
     * Get composer.
     *
     * @return Composer
     * @throws ComposerException
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
     * @return PackageSet
     */
    public function getPackages(): PackageSet
    {
        if (null === $this->packages) {
            $this->packages = new PackageSet();
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
     * @return Core
     * @throws BerliozException
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
     * @return DirectoriesInterface
     */
    public function getDirectories(): DirectoriesInterface
    {
        return $this->directories;
    }
}