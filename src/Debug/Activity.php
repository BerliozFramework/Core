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

namespace Berlioz\Core\Debug;

class Activity
{
    /** @var string Unique ID */
    private $uniqid;
    /** @var string Name */
    private $name;
    /** @var string|null Group */
    private $group;
    /** @var string|null Description */
    private $description;
    /** @var string|null Detail */
    private $detail;
    /** @var mixed|null Result */
    private $result;
    /** @var float Start micro time */
    private $startMicroTime;
    /** @var int Start memory usage */
    private $startMemoryUsage;
    /** @var int Start memory peak usage */
    private $startMemoryPeakUsage;
    /** @var float End micro time */
    private $endMicroTime;
    /** @var int End memory usage */
    private $endMemoryUsage;
    /** @var int End memory peak usage */
    private $endMemoryPeakUsage;

    /**
     * Activity constructor.
     *
     * @param string      $name
     * @param null|string $group
     */
    public function __construct(string $name, string $group = 'Application')
    {
        $this->uniqid = uniqid();
        $this->name = $name;
        $this->group = $group;
    }

    /**
     * Get unique ID.
     *
     * @return string
     */
    public function getUniqId(): string
    {
        return $this->uniqid;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get group.
     *
     * @return null|string
     */
    public function getGroup(): ?string
    {
        return $this->group;
    }

    /**
     * Get description.
     *
     * @return null|string
     */
    public function getDescription(): ?string
    {
        return $this->description ?: sprintf('%s / %s', $this->getGroup(), $this->getName());
    }

    /**
     * Set description.
     *
     * @param null|string $description
     *
     * @return Activity
     */
    public function setDescription(?string $description): Activity
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get detail.
     *
     * @return null|string
     */
    public function getDetail(): ?string
    {
        return $this->detail;
    }

    /**
     * Set detail.
     *
     * @param null|string $detail
     *
     * @return Activity
     */
    public function setDetail(?string $detail): Activity
    {
        $this->detail = $detail;

        return $this;
    }

    /**
     * Get result.
     *
     * @return mixed|null
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Set result.
     *
     * @param mixed|null $result
     *
     * @return Activity
     */
    public function setResult($result = null): Activity
    {
        $this->result = $result;

        return $this;
    }

    /**
     * Get start micro time.
     *
     * @return float
     */
    public function getStartMicroTime(): float
    {
        return $this->startMicroTime;
    }

    /**
     * Get start memory usage.
     *
     * @return int
     */
    public function getStartMemoryUsage(): int
    {
        return $this->startMemoryUsage;
    }

    /**
     * Get start memory peak usage.
     *
     * @return int
     */
    public function getStartMemoryPeakUsage(): int
    {
        return $this->startMemoryPeakUsage;
    }

    /**
     * Get end micro time.
     *
     * @return float
     */
    public function getEndMicroTime(): float
    {
        return $this->endMicroTime;
    }

    /**
     * Get end memory usage.
     *
     * @return int
     */
    public function getEndMemoryUsage(): int
    {
        return $this->endMemoryUsage;
    }

    /**
     * Get end memory peak usage.
     *
     * @return int
     */
    public function getEndMemoryPeakUsage(): int
    {
        return $this->endMemoryPeakUsage;
    }

    ////////////////////////////////
    /// TimeLine Getters/Setters ///
    ////////////////////////////////

    /**
     * Start activity.
     *
     * @param float|null $startMicroTime
     * @param int|null   $startMemoryUsage     Memory usage, default: memory_get_usage()
     * @param int|null   $startMemoryPeakUsage Memory peak usage, default: memory_get_peak_usage()
     *
     * @return static
     */
    public function start(?float $startMicroTime = null, ?int $startMemoryUsage = null, ?int $startMemoryPeakUsage = null): Activity
    {
        if (is_null($startMicroTime)) {
            $startMicroTime = microtime(true);
        }

        if (is_null($startMemoryUsage)) {
            $startMemoryUsage = memory_get_usage();
        }
        if (is_null($startMemoryPeakUsage)) {
            $startMemoryPeakUsage = memory_get_peak_usage();
        }

        $this->startMicroTime = $startMicroTime;
        $this->startMemoryUsage = $startMemoryUsage;
        $this->startMemoryPeakUsage = $startMemoryPeakUsage;

        return $this;
    }

    /**
     * End activity.
     *
     * @param float|null $endMicroTime
     * @param int|null   $endMemoryUsage     Memory usage, default: memory_get_usage()
     * @param int|null   $endMemoryPeakUsage Memory peak usage, default: memory_get_peak_usage()
     *
     * @return static
     */
    public function end(?float $endMicroTime = null, ?int $endMemoryUsage = null, ?int $endMemoryPeakUsage = null): Activity
    {
        if (is_null($endMicroTime)) {
            $endMicroTime = microtime(true);
        }

        if (is_null($endMemoryUsage)) {
            $endMemoryUsage = memory_get_usage();
        }
        if (is_null($endMemoryPeakUsage)) {
            $endMemoryPeakUsage = memory_get_peak_usage();
        }

        $this->endMicroTime = $endMicroTime;
        $this->endMemoryUsage = $endMemoryUsage;
        $this->endMemoryPeakUsage = $endMemoryPeakUsage;

        return $this;
    }

    /**
     * Get duration of activity.
     *
     * @return float|null
     */
    public function duration(): ?float
    {
        if (is_null($this->startMicroTime) || is_null($this->endMicroTime)) {
            return null;
        }

        return $this->endMicroTime - $this->startMicroTime;
    }
}