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

use Berlioz\Core\App\AbstractApp;
use Berlioz\Core\App\AppAwareInterface;
use Berlioz\Core\App\AppAwareTrait;
use Berlioz\Core\Debug\PhpError;
use Berlioz\Core\Debug\Section;
use Berlioz\Core\Debug\TimeLine;

class Debug implements AppAwareInterface, \Serializable
{
    use AppAwareTrait;
    /** @var bool Enabled? */
    protected $enabled;
    /** @var string Unique ID */
    protected $uniqid;
    /** @var \DateTime Date time of debug */
    protected $datetime;
    /** @var array System info */
    protected $systemInfo;
    /** @var array PHP info */
    protected $phpInfo;
    /** @var array Performances info */
    protected $performancesInfo;
    /** @var array Project info */
    protected $projectInfo;
    /** @var array Config */
    protected $config;
    /** @var \Berlioz\Core\Debug\TimeLine */
    protected $timeLine;
    /** @var \Berlioz\Core\Debug\PhpError */
    protected $phpError;
    /** @var \Berlioz\Core\Debug\Section[] */
    protected $sections;

    /**
     * Debug constructor.
     *
     * @param \Berlioz\Core\App\AbstractApp $app
     */
    public function __construct(AbstractApp $app)
    {
        $this->setApp($app);
        $this->uniqid = uniqid();
        $this->datetime = new \DateTime;

        $this->systemInfo = [];
        $this->phpInfo = [];
        $this->performancesInfo = [];
        $this->projectInfo = [];
        $this->timeLine = new TimeLine;
        $this->phpError = (new PhpError)->handle();
        $this->sections = [];
    }

    /**
     * @inheritdoc
     */
    public function serialize(): string
    {
        return serialize(['uniqid'           => $this->uniqid,
                          'datetime'         => $this->datetime,
                          'systemInfo'       => $this->getSystemInfo(),
                          'phpInfo'          => $this->getPhpInfo(),
                          'performancesInfo' => $this->getPerformancesInfo(),
                          'projectInfo'      => $this->getProjectInfo(),
                          'config'           => $this->getConfig() ?? [],
                          'timeLine'         => $this->timeLine,
                          'phpError'         => $this->phpError,
                          'sections'         => $this->sections]);
    }

    /**
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        $unserialized = unserialize($serialized);

        $this->uniqid = $unserialized['uniqid'] ?? uniqid();
        $this->datetime = $unserialized['datetime'] ?? new \DateTime;
        $this->systemInfo = $unserialized['systemInfo'] ?? [];
        $this->phpInfo = $unserialized['phpInfo'] ?? [];
        $this->performancesInfo = $unserialized['performancesInfo'] ?? [];
        $this->projectInfo = $unserialized['projectInfo'] ?? [];
        $this->config = $unserialized['config'] ?? [];
        $this->timeLine = $unserialized['timeLine'] ?? new TimeLine;
        $this->phpError = $unserialized['phpError'] ?? new PhpError;
        $this->sections = $unserialized['sections'] ?? [];
    }

    /**
     * Is enabled?
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        if (is_null($this->enabled)) {
            try {
                return $this->getApp()->getConfig()->get('berlioz.debug', false);
            } catch (\Throwable $e) {
                return false;
            }
        } else {
            return $this->enabled;
        }
    }

    /**
     * Set enabled.
     *
     * @param bool $enabled
     *
     * @return static
     */
    public function setEnabled(bool $enabled = true): Debug
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Get unique ID.
     *
     * @return string
     */
    public function getUniqid(): string
    {
        return $this->uniqid;
    }

    /**
     * Get date time.
     *
     * @return \DateTime
     */
    public function getDatetime(): \DateTime
    {
        return $this->datetime;
    }

    /**
     * Get system info.
     *
     * @return array
     */
    public function getSystemInfo(): array
    {
        if (empty($this->systemInfo)) {
            $this->systemInfo = ['uname'        => php_uname(),
                                 'current_user' => get_current_user(),
                                 'uid'          => getmyuid(),
                                 'gid'          => getmygid(),
                                 'pid'          => getmypid(),
                                 'inode'        => getmyinode(),
                                 'tmp_dir'      => sys_get_temp_dir()];
        }

        return $this->systemInfo;
    }

    /**
     * Get PHP info.
     *
     * @return array
     */
    public function getPhpInfo(): array
    {
        if (empty($this->phpInfo)) {
            $this->phpInfo = ['php_version'  => phpversion(),
                              'sapi'         => php_sapi_name(),
                              'memory_limit' => b_size_from_ini(ini_get('memory_limit')),
                              'extensions'   => get_loaded_extensions()];
        }

        return $this->phpInfo;
    }

    /**
     * Get performances info.
     *
     * @return array
     */
    public function getPerformancesInfo(): array
    {
        if (empty($this->performancesInfo)) {
            $this->performancesInfo = ['loadavg'           => function_exists('sys_getloadavg') ? sys_getloadavg() : [],
                                       'memory_peak_usage' => memory_get_peak_usage()];
        }

        return $this->performancesInfo;
    }

    /**
     * Get project info.
     *
     * @return array
     */
    public function getProjectInfo(): array
    {
        if (empty($this->projectInfo)) {
            $this->projectInfo = ['declared_classes' => get_declared_classes(),
                                  'included_files'   => get_included_files()];
        }

        return $this->projectInfo;
    }

    /**
     * Get config.
     *
     * @return array
     */
    public function getConfig(): array
    {
        if (is_null($this->config)) {
            try {
                $this->config = $this->getApp()->getConfig()->get();
            } catch (\Throwable $e) {
                $this->config = [];
            }
        }

        return $this->config;
    }

    /**
     * Get time line.
     *
     * @return \Berlioz\Core\Debug\TimeLine
     */
    public function getTimeLine(): TimeLine
    {
        return $this->timeLine;
    }

    /**
     * Get PHP error handler.
     *
     * @return \Berlioz\Core\Debug\PhpError
     */
    public function getPhpError(): PhpError
    {
        return $this->phpError;
    }

    /**
     * Get sections.
     *
     * @return \Berlioz\Core\Debug\Section[]
     */
    public function getSections(): array
    {
        return array_filter($this->sections);
    }

    /**
     * Get section.
     *
     * @param string $id
     *
     * @return \Berlioz\Core\Debug\Section|null
     */
    public function getSection(string $id): ?Section
    {
        return $this->sections[$id] ?? null;
    }

    /**
     * Add section.
     *
     * @param \Berlioz\Core\Debug\Section $section Section
     *
     * @return static
     */
    public function addSection(Section $section): Debug
    {
        $this->sections[$section->getSectionId()] = $section;

        return $this;
    }
}