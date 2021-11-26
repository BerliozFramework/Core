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
use Berlioz\Core\Debug\Activity;
use Berlioz\Core\Debug\PhpError;
use Berlioz\Core\Debug\Section;
use Berlioz\Core\Debug\TimeLine;
use Berlioz\Core\Exception\BerliozException;
use DateTime;
use Serializable;
use Throwable;

/**
 * Class Debug.
 *
 * @package Berlioz\Core
 */
class Debug implements Serializable
{
    /** @var bool Enabled? */
    protected $enabled;
    /** @var string Unique ID */
    protected $uniqid;
    /** @var DateTime Date time of debug */
    protected $datetime;
    /** @var array System info */
    protected $systemInfo = [];
    /** @var array PHP info */
    protected $phpInfo = [];
    /** @var array Performances info */
    protected $performancesInfo = [];
    /** @var array Project info */
    protected $projectInfo = [];
    /** @var ConfigInterface Config */
    protected $config;
    /** @var TimeLine */
    protected $timeLine;
    /** @var PhpError */
    protected $phpError;
    /** @var string|null Exception */
    protected $exception = null;
    /** @var Section[] */
    protected $sections = [];

    /**
     * Debug constructor.
     *
     * @param Core $core
     *
     * @throws BerliozException
     */
    public function __construct(Core $core)
    {
        try {
            $this->setEnabled($this->isEnabledInConfig($core->getConfig()));
            $this->uniqid = uniqid();
            $this->datetime = new DateTime();
            $this->config = $core->getConfig();
            $this->timeLine = new TimeLine();
            $this->phpError = (new PhpError())->handle();
        } catch (Throwable $e) {
            throw new BerliozException('Unable to init Debug class', 0, $e);
        }
    }

    public function __serialize(): array
    {
        return [
            'uniqid' => $this->uniqid,
            'datetime' => $this->datetime,
            'systemInfo' => $this->systemInfo,
            'phpInfo' => $this->phpInfo,
            'performancesInfo' => $this->performancesInfo,
            'projectInfo' => $this->projectInfo,
            'config' => $this->config,
            'timeLine' => $this->timeLine,
            'phpError' => $this->phpError,
            'exception' => $this->exception,
            'sections' => $this->sections,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->uniqid = $data['uniqid'] ?? uniqid();
        $this->datetime = $data['datetime'] ?? new DateTime();
        $this->systemInfo = $data['systemInfo'] ?? [];
        $this->phpInfo = $data['phpInfo'] ?? [];
        $this->performancesInfo = $data['performancesInfo'] ?? [];
        $this->projectInfo = $data['projectInfo'] ?? [];
        $this->config = $data['config'] ?? [];
        $this->timeLine = $data['timeLine'] ?? new TimeLine();
        $this->phpError = $data['phpError'] ?? new PhpError();
        $this->exception = $data['exception'] ?? null;
        $this->sections = $data['sections'] ?? [];
    }

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        return serialize($this->__serialize());
    }

    /**
     * @inheritdoc
     */
    public function unserialize($serialized): void
    {
        $unserialized = unserialize($serialized);
        $this->__unserialize($unserialized);
    }

    /**
     * Is enabled?
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled == true;
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
     * Is enabled in config?
     *
     * @param ConfigInterface $config
     *
     * @return bool
     * @throws ConfigException
     */
    public function isEnabledInConfig(ConfigInterface $config): bool
    {
        $debug = $config->get('berlioz.debug.enable', false);

        if (!is_bool($debug) || false === $debug) {
            return false;
        }

        // Get ip addresses from config
        $configIpAddresses = $debug = $config->get('berlioz.debug.ip', []);
        if (!is_array($configIpAddresses)) {
            return false;
        }

        // No ip restriction
        if (empty($configIpAddresses)) {
            return true;
        }

        // Get ip
        $serverIpAddresses = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;

        if (null === $serverIpAddresses) {
            return false;
        }

        foreach (explode(",", $serverIpAddresses) as $ipAddress) {
            $ipAddress = trim($ipAddress);

            // Find ip
            if (in_array($ipAddress, $configIpAddresses)) {
                return true;
            }

            // Find host
            if (in_array(gethostbyaddr($ipAddress), $configIpAddresses)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Save report.
     */
    public function saveReport(): string
    {
        // Infos
        $this->systemInfo = [
            'uname' => php_uname(),
            'current_user' => get_current_user(),
            'uid' => getmyuid(),
            'gid' => getmygid(),
            'pid' => getmypid(),
            'inode' => getmyinode(),
            'tmp_dir' => sys_get_temp_dir(),
        ];
        $this->phpInfo = [
            'php_version' => phpversion(),
            'sapi' => php_sapi_name(),
            'memory_limit' => b_size_from_ini(ini_get('memory_limit')),
            'extensions' => get_loaded_extensions(),
        ];
        $this->performancesInfo = [
            'loadavg' => function_exists('sys_getloadavg') ? sys_getloadavg() : [],
            'memory_peak_usage' => memory_get_peak_usage(),
        ];
        $this->projectInfo = [
            'declared_classes' => get_declared_classes(),
            'included_files' => get_included_files(),
        ];

        // Call saveReport() method on all sections
        foreach ($this->sections as $section) {
            $section->saveReport();
        }

        return gzdeflate(serialize($this));
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
     * @return DateTime
     */
    public function getDatetime(): DateTime
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
        return $this->systemInfo;
    }

    /**
     * Get PHP info.
     *
     * @return array
     */
    public function getPhpInfo(): array
    {
        return $this->phpInfo;
    }

    /**
     * Get performances info.
     *
     * @return array
     */
    public function getPerformancesInfo(): array
    {
        return $this->performancesInfo;
    }

    /**
     * Get project info.
     *
     * @return array
     */
    public function getProjectInfo(): array
    {
        return $this->projectInfo;
    }

    /**
     * Get config.
     *
     * @return ConfigInterface
     */
    public function getConfig(): ConfigInterface
    {
        return $this->config;
    }

    /**
     * Get time line.
     *
     * @return TimeLine
     */
    public function getTimeLine(): TimeLine
    {
        return $this->timeLine;
    }

    /**
     * New time line activity.
     *
     * @param string $name
     * @param string $group
     *
     * @return Activity
     */
    public function newActivity(string $name, string $group = 'Application'): Activity
    {
        $this->getTimeLine()->addActivity($activity = new Activity($name, $group));

        return $activity;
    }

    /**
     * Get PHP error handler.
     *
     * @return PhpError
     */
    public function getPhpError(): PhpError
    {
        return $this->phpError;
    }

    /**
     * Has exception thrown?
     *
     * @return bool
     */
    public function hasExceptionThrown(): bool
    {
        return null !== $this->exception;
    }

    /**
     * Get exception thrown (trace).
     *
     * @return string|null
     */
    public function getExceptionThrown(): ?string
    {
        return $this->exception;
    }

    /**
     * Set exception thrown.
     *
     * @param Throwable $e
     *
     * @return static
     */
    public function setExceptionThrown(Throwable $e): Debug
    {
        $this->exception = (string)$e;

        return $this;
    }

    /**
     * Get sections.
     *
     * @return Section[]
     */
    public function getSections(): array
    {
        return array_filter($this->sections);
    }

    /**
     * Get section.
     *
     * @param string $sectionId
     *
     * @return Section|null
     */
    public function getSection(string $sectionId): ?Section
    {
        return $this->sections[$sectionId] ?? null;
    }

    /**
     * Add section.
     *
     * @param Section $section Section
     *
     * @return static
     */
    public function addSection(Section $section): Debug
    {
        $this->sections[$section->getSectionId()] = $section;

        return $this;
    }
}
