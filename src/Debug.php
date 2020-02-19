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
class Debug implements CoreAwareInterface, Serializable
{
    use CoreAwareTrait;
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
    /** @var string|null Exception */
    protected $exception;
    /** @var \Berlioz\Core\Debug\Section[] */
    protected $sections;

    /**
     * Debug constructor.
     *
     * @param \Berlioz\Core\Core $core
     *
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function __construct(Core $core)
    {
        try {
            $this->setCore($core);
            $this->uniqid = uniqid();
            $this->datetime = new DateTime();

            $this->systemInfo = [];
            $this->phpInfo = [];
            $this->performancesInfo = [];
            $this->projectInfo = [];
            $this->timeLine = new TimeLine();
            $this->phpError = (new PhpError())->handle();
            $this->sections = [];
        } catch (Throwable $e) {
            throw new BerliozException('Unable to init Debug class', 0, $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        return serialize(
            [
                'uniqid' => $this->uniqid,
                'datetime' => $this->datetime,
                'systemInfo' => $this->getSystemInfo(),
                'phpInfo' => $this->getPhpInfo(),
                'performancesInfo' => $this->getPerformancesInfo(),
                'projectInfo' => $this->getProjectInfo(),
                'config' => $this->getConfig() ?? [],
                'timeLine' => $this->timeLine,
                'phpError' => $this->phpError,
                'exception' => $this->exception,
                'sections' => $this->sections,
            ]
        );
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function unserialize($serialized)
    {
        $unserialized = unserialize($serialized);

        $this->uniqid = $unserialized['uniqid'] ?? uniqid();
        $this->datetime = $unserialized['datetime'] ?? new DateTime();
        $this->systemInfo = $unserialized['systemInfo'] ?? [];
        $this->phpInfo = $unserialized['phpInfo'] ?? [];
        $this->performancesInfo = $unserialized['performancesInfo'] ?? [];
        $this->projectInfo = $unserialized['projectInfo'] ?? [];
        $this->config = $unserialized['config'] ?? [];
        $this->timeLine = $unserialized['timeLine'] ?? new TimeLine();
        $this->phpError = $unserialized['phpError'] ?? new PhpError();
        $this->exception = $unserialized['exception'] ?? null;
        $this->sections = $unserialized['sections'] ?? [];
    }

    /**
     * Is enabled?
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        if (null !== $this->enabled) {
            return $this->enabled;
        }

        try {
            $debug = $this->getCore()->getConfig()->get('berlioz.debug', false);

            if (is_bool($debug)) {
                return $this->enabled = $debug;
            }

            // Get ip
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;

            if (null === $ipAddress) {
                return $this->enabled = false;
            }

            // Find ip
            if (in_array($ipAddress, $debug)) {
                return $this->enabled = true;
            }

            // Find host
            if (in_array(gethostbyaddr($ipAddress), $debug)) {
                return $this->enabled = true;
            }

            return $this->enabled = false;
        } catch (Throwable $e) {
            return false;
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
     * Save report.
     *
     * @throws \Berlioz\Config\Exception\ConfigException
     */
    public function saveReport()
    {
        foreach ($this->sections as $section) {
            $section->saveReport();
        }

        if (!empty($debugDirectory = $this->getCore()->getConfig()->get('berlioz.directories.debug'))) {
            if (is_dir($debugDirectory) || mkdir($debugDirectory, 0777, true)) {
                file_put_contents(
                    $debugDirectory . DIRECTORY_SEPARATOR . $this->getUniqid() . '.debug',
                    gzdeflate(serialize($this))
                );
            }
        }
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
        if (empty($this->systemInfo)) {
            $this->systemInfo = [
                'uname' => php_uname(),
                'current_user' => get_current_user(),
                'uid' => getmyuid(),
                'gid' => getmygid(),
                'pid' => getmypid(),
                'inode' => getmyinode(),
                'tmp_dir' => sys_get_temp_dir(),
            ];
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
            $this->phpInfo = [
                'php_version' => phpversion(),
                'sapi' => php_sapi_name(),
                'memory_limit' => b_size_from_ini(ini_get('memory_limit')),
                'extensions' => get_loaded_extensions(),
            ];
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
            $this->performancesInfo = [
                'loadavg' => function_exists('sys_getloadavg') ? sys_getloadavg() : [],
                'memory_peak_usage' => memory_get_peak_usage(),
            ];
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
            $this->projectInfo = [
                'declared_classes' => get_declared_classes(),
                'included_files' => get_included_files(),
            ];
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
        if (null === $this->config) {
            try {
                $this->config = $this->getCore()->getConfig()->get();
            } catch (Throwable $e) {
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
     * @param \Throwable $e
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
     * @return \Berlioz\Core\Debug\Section[]
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
     * @return \Berlioz\Core\Debug\Section|null
     */
    public function getSection(string $sectionId): ?Section
    {
        return $this->sections[$sectionId] ?? null;
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