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

use Berlioz\Config\ConfigAwareInterface;
use Berlioz\Config\ConfigAwareTrait;
use Berlioz\Config\ConfigInterface;
use Berlioz\Core\Debug;
use Berlioz\Core\Exception\BerliozException;
use Berlioz\ServiceContainer\ServiceContainer;
use Berlioz\ServiceContainer\ServiceContainerAwareInterface;
use Berlioz\ServiceContainer\ServiceContainerAwareTrait;

abstract class AbstractApp implements ConfigAwareInterface, ServiceContainerAwareInterface
{
    use ConfigAwareTrait;
    use ServiceContainerAwareTrait;
    /** @var \Berlioz\Core\Debug */
    protected $debug;
    /** @var string Root directory */
    protected $rootDirectory;
    /** @var string App directory */
    protected $appDirectory;
    /** @var string Locale */
    private $locale;

    /**
     * AbstractApp constructor.
     */
    public function __construct()
    {
        if ($_SERVER['REQUEST_TIME_FLOAT']) {
            $this->getDebug()
                 ->getTimeLine()
                 ->addActivity((new Debug\Activity('PHP initialization', 'Berlioz'))
                                   ->start($_SERVER['REQUEST_TIME_FLOAT'])
                                   ->end());
        }
    }

    /**
     * AbstractApp destructor.
     */
    public function __destruct()
    {
        try {
            if ($this->getDebug()->isEnabled()) {
                if (!empty($debugDirectory = $this->getConfig()->get('berlioz.directories.debug'))) {
                    if (is_dir($debugDirectory) || mkdir($debugDirectory, 0777, true)) {
                        file_put_contents($debugDirectory . DIRECTORY_SEPARATOR . $this->getDebug()->getUniqid() . '.debug',
                                          gzdeflate(serialize($this->getDebug())));
                    }
                }
            }
        } catch (\Throwable $e) {
        }
    }

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

    /**
     * @inheritdoc
     */
    public function setConfig(ConfigInterface $config)
    {
        $this->config = $config;

        // Init default configuration
        $config->setVariable('berlioz.directories.root', $this->getRootDir());
        $config->setVariable('berlioz.directories.app', $this->getAppDir());
        $config->setVariable('directory_separator', DIRECTORY_SEPARATOR);
    }

    /**
     * Get service container.
     *
     * @return \Berlioz\ServiceContainer\ServiceContainer
     * @throws \Berlioz\Config\Exception\ConfigException
     */
    public function getServiceContainer(): ServiceContainer
    {
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
    }

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
            // Search composer.json for app directory
            $directory = $this->getRootDir();
            $i = 0;
            do {
                $directoryBefore = $directory;

                if (file_exists($directory . DIRECTORY_SEPARATOR . 'composer.json')) {
                    $this->appDirectory = $directory;
                }

                $directory = realpath($directory . DIRECTORY_SEPARATOR . '..');
                $i++;
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
}