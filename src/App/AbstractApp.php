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

namespace Berlioz\Core\App;

use Berlioz\Core\Config;
use Berlioz\Core\Debug;
use Berlioz\Core\Exception\BerliozException;
use Berlioz\Core\Exception\ConfigException;
use Berlioz\Core\Exception\PackageException;
use Berlioz\Core\Package\PackageInterface;
use Berlioz\ServiceContainer\ServiceContainer;
use Berlioz\ServiceContainer\ServiceContainerAwareInterface;
use Berlioz\ServiceContainer\ServiceContainerAwareTrait;

abstract class AbstractApp implements ServiceContainerAwareInterface, \Serializable
{
    use ServiceContainerAwareTrait;
    /** @var \Berlioz\Core\Debug */
    protected $debug;
    /** @var \Berlioz\Core\Config Configuration */
    private $config;
    /** @var string Locale */
    private $locale;
    /** @var \Berlioz\Core\Package\PackageInterface[] Packages */
    private $packages;
    // Directories
    /** @var string Root directory */
    protected $rootDirectory;
    /** @var string App directory */
    protected $appDirectory;
    /** @var string Config directory */
    protected $configDirectory;

    /**
     * AbstractApp constructor.
     *
     * @throws \Berlioz\Core\Exception\PackageException
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * AbstractApp destructor.
     */
    public function __destruct()
    {
        try {
            if ($this->getDebug()->isEnabled()) {
                $this->getDebug()->saveReport();
            }
        } catch (\Throwable $e) {
        }
    }

    /**
     * Initialization.
     *
     * @throws \Berlioz\Core\Exception\PackageException
     */
    private function init()
    {
        if ($_SERVER['REQUEST_TIME_FLOAT']) {
            $this->getDebug()
                 ->getTimeLine()
                 ->addActivity((new Debug\Activity('PHP initialization', 'Berlioz'))
                                   ->start($_SERVER['REQUEST_TIME_FLOAT'])
                                   ->end());
        }

        $this->loadPackages();
    }

    /////////////////////
    /// SERIALIZATION ///
    /////////////////////

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        return serialize(['config'           => $this->config,
                          'locale'           => $this->locale,
                          'rootDirectory'    => $this->rootDirectory,
                          'appDirectory'     => $this->appDirectory,
                          'configDirectory'  => $this->configDirectory,
                          'serviceContainer' => $this->serviceContainer]);
    }

    /**
     * @inheritdoc
     * @throws \Berlioz\Core\Exception\PackageException
     */
    public function unserialize($serialized)
    {
        $tmpUnserialized = unserialize($serialized);

        $this->config = $tmpUnserialized['config'];
        $this->locale = $tmpUnserialized['locale'];
        $this->rootDirectory = $tmpUnserialized['rootDirectory'];
        $this->appDirectory = $tmpUnserialized['appDirectory'];
        $this->configDirectory = $tmpUnserialized['configDirectory'];
        $this->serviceContainer = $tmpUnserialized['serviceContainer'];

        $this->init();
    }

    //////////////
    /// DEBUG ///
    //////////////

    /**
     * Get debug manager.
     *
     * @return \Berlioz\Core\Debug
     */
    public function getDebug(): Debug
    {
        if (is_null($this->debug)) {
            $this->debug = new Debug($this);
        }

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
        try {
            if (is_null($this->config)) {
                $configActivity = (new Debug\Activity('Config (initialization)', 'Berlioz'))->start();

                // Create configuration (from default configuration)
                $config = new Config(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'resources', 'config.default.json']), true);

                // Init default configuration
                $config->setVariable('berlioz.directories.config', $this->getConfigDir());
                $config->setVariable('berlioz.directories.root', $this->getRootDir());
                $config->setVariable('berlioz.directories.app', $this->getAppDir());
                $config->setVariable('directory_separator', DIRECTORY_SEPARATOR);

                // Search website configs and add
                foreach (glob($this->getConfigDir() . DIRECTORY_SEPARATOR . '*.json') as $configFile) {
                    $config->extendsJson($configFile, true, false);
                }

                $this->getDebug()->getTimeLine()->addActivity($configActivity->end());

                // Set config to parent
                $this->config = $config;
            }

            return $this->config;
        } catch (BerliozException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new ConfigException('Configuration error', 0, $e);
        }
    }

    /**
     * Is config initialized?
     */
    public function isConfigInitialized()
    {
        return !is_null($this->config);
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
        try {
            if (!$this->hasServiceContainer()) {
                $serviceContainerActivity = (new Debug\Activity('Service container (initialization)', 'Berlioz'))->start();

                $servicesConfig = $this->getConfig()->get('services', []);
                $servicesConstraints = $servicesConfig['_constraints'] ?: [];
                unset($servicesConfig['_constraints']);

                // Init service container with constraints
                $this->setServiceContainer($serviceContainer = new ServiceContainer($servicesConfig, $servicesConstraints));

                // Register default services
                $serviceContainer->register('berlioz', $this);
                $serviceContainer->register('config', $this->getConfig());

                $this->getDebug()->getTimeLine()->addActivity($serviceContainerActivity->end());
            }

            return $this->serviceContainer;
        } catch (\Berlioz\Config\Exception\ConfigException $e) {
            throw new ConfigException('Configuration error', 0, $e);
        }
    }

    ////////////////
    /// PACKAGES ///
    ////////////////

    /**
     * Load packages.
     *
     * @return void
     * @throws \Berlioz\Core\Exception\PackageException
     */
    protected function loadPackages()
    {
        $packagesActivity = (new Debug\Activity('Packages (instantiation)', 'Berlioz'))->start();

        // Get packages from PHP file
        try {
            $this->packages = [];
            foreach ($this->getConfig()->get('packages', []) as $packageClass) {
                if (!is_a($packageClass, PackageInterface::class, true)) {
                    if (class_exists($packageClass)) {
                        throw new PackageException(sprintf('Package class "%s" does not exists', $packageClass));
                    } else {
                        throw new PackageException(sprintf('Package class "%s" must implements "%s" interface', $packageClass, PackageInterface::class));
                    }
                }

                // Create instance of package
                $package = $this->getServiceContainer()
                                ->getInstantiator()
                                ->newInstanceOf($packageClass);

                // Add package
                $this->packages[get_class($package)] = $package;
            }
        } catch (PackageException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new PackageException('Error during packages loading', 0, $e);
        }

        $this->initPackages();

        $this->getDebug()->getTimeLine()->addActivity($packagesActivity->end());
    }

    /**
     * Init packages.
     *
     * @throws \Berlioz\Core\Exception\PackageException
     */
    protected function initPackages()
    {
        /**
         * @var string                                 $packageClass
         * @var \Berlioz\Core\Package\PackageInterface $package
         */
        foreach ($this->packages as $packageClass => $package) {
            try {
                // Load default configuration
                if (!is_null($configFileName = $package->getDefaultConfigFilename())) {
                    $this->getConfig()->extendsJson($configFileName, true, true);
                }

                // Init package
                $package->init();
            } catch (\Throwable $e) {
                throw new PackageException(sprintf('Error during initialization of package: "%s"', $packageClass), 0, $e);
            }
        }
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
     * @return \Berlioz\Core\App\AbstractApp
     * @throws \Berlioz\Core\Exception\BerliozException
     * @see \Locale
     */
    public function setLocale(string $locale): AbstractApp
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
     * Get root directory.
     *
     * @return string
     */
    public function getRootDir(): string
    {
        if (is_null($this->rootDirectory)) {
            // Get document root from server configuration
            if (getenv('DOCUMENT_ROOT') !== false) {
                $this->rootDirectory = rtrim(getenv('DOCUMENT_ROOT'), '\\/');
            } else {
                $this->rootDirectory = getcwd() ?: __DIR__;
            }
        }

        return $this->rootDirectory;
    }

    /**
     * Set root directory.
     *
     * @param string $rootDirectory
     *
     * @return static
     */
    public function setRootDir(string $rootDirectory): AbstractApp
    {
        $this->rootDirectory = $rootDirectory;

        return $this;
    }

    /**
     * Get app directory.
     *
     * Find last composer.json file.
     *
     * @return string
     */
    public function getAppDir(): string
    {
        if (is_null($this->appDirectory)) {
            $myComposerFilename = realpath(__DIR__ . '/../../composer.json');

            // Search composer.json for app directory
            $directory = $this->getRootDir();
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
                $this->appDirectory = $this->getRootDir();
            }
        }

        return $this->appDirectory;
    }

    /**
     * Set application directory.
     *
     * @param string $appDirectory
     *
     * @return static
     */
    public function setAppDir(string $appDirectory): AbstractApp
    {
        $this->appDirectory = $appDirectory;

        return $this;
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
     * Set config directory.
     *
     * @param string $configDirectory
     *
     * @return static
     */
    public function setConfigDir(string $configDirectory): AbstractApp
    {
        $this->configDirectory = $configDirectory;

        return $this;
    }
}