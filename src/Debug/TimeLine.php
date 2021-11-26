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

namespace Berlioz\Core\Debug;

use Countable;

/**
 * Class TimeLine.
 *
 * @package Berlioz\Core\Debug
 */
class TimeLine extends AbstractSection implements Countable
{
    /** @var Activity[] Activities */
    private $activities = [];

    /////////////////////////
    /// SECTION INTERFACE ///
    /////////////////////////

    /**
     * @inheritdoc
     */
    public function __toString(): string
    {
        return var_export($this->activities, true);
    }

    /**
     * @inheritdoc
     */
    public function getSectionName(): string
    {
        return 'Activities';
    }

    public function __serialize(): array
    {
        return $this->activities;
    }

    public function __unserialize(array $data): void
    {
        $this->activities = $data;
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

    ///////////////////////////
    /// COUNTABLE INTERFACE ///
    ///////////////////////////

    /**
     * @inheritdoc
     */
    public function count(): int
    {
        return count($this->activities);
    }

    ////////////////////
    /// USER DEFINED ///
    ////////////////////

    /**
     * Get groups.
     *
     * @return string[]
     */
    public function getGroups(): array
    {
        $groups = [];

        foreach ($this->activities as $activity) {
            $groups[] = $activity->getGroup();
        }

        return array_unique($groups);
    }

    /**
     * Order activities.
     *
     * @param Activity[] $activities
     */
    private function orderActivities(array &$activities)
    {
        usort(
            $activities,
            function (Activity $activityA, Activity $activityB) {
                $aTime = $activityA->getStartMicroTime();
                $bTime = $activityB->getStartMicroTime();

                return $aTime == $bTime ? 0 : (($aTime < $bTime) ? -1 : 1);
            }
        );
    }

    /**
     * Add activity.
     *
     * @param Activity $activity
     *
     * @return TimeLine
     */
    public function addActivity(Activity $activity): TimeLine
    {
        $this->activities[] = $activity;

        return $this;
    }

    /**
     * Get activities.
     *
     * @param string|null $group Group name, null for all
     *
     * @return Activity[]
     */
    public function getActivities(string $group = null): array
    {
        $this->orderActivities($this->activities);

        if (null === $group) {
            return $this->activities;
        }

        return array_filter(
            $this->activities,
            function (Activity $activity) use ($group) {
                return $activity->getGroup() == $group;
            }
        );
    }

    /**
     * Get first time.
     *
     * @param string|null $group Group name, null for all
     *
     * @return float|null
     */
    public function getFirstTime(string $group = null): ?float
    {
        $activities = $this->getActivities($group);
        $activities = array_map(
            function (Activity $activity) {
                return $activity->getStartMicroTime();
            },
            $activities
        );
        $activities = array_filter($activities);

        if (empty($activities)) {
            return null;
        }

        return min($activities);
    }

    /**
     * Get last time.
     *
     * @param string|null $group Group name, null for all
     *
     * @return float|null
     */
    public function getLastTime(string $group = null): ?float
    {
        $activities = $this->getActivities($group);
        $activities = array_map(
            function (Activity $activity) {
                return $activity->getEndMicroTime();
            },
            $activities
        );
        $activities = array_filter($activities);

        if (empty($activities)) {
            return null;
        }

        return max($activities);
    }

    /**
     * Get duration.
     *
     * @param string|null $group Group name, null for all
     *
     * @return float|null
     */
    public function getDuration(string $group = null): ?float
    {
        $firstTime = $this->getFirstTime($group);
        $lastTime = $this->getLastTime($group);

        if (null === $firstTime || null === $lastTime) {
            return null;
        }

        return $lastTime - $firstTime;
    }

    /**
     * Get memory usages.
     *
     * @param string|null $group Group name, null for all
     *
     * @return array[]
     */
    public function getMemoryUsages(string $group = null): array
    {
        $memoryUsages = [];
        $firstTime = $this->getFirstTime($group);

        if (null === $firstTime) {
            return [];
        }

        $activities = $this->getActivities($group);
        $activities = array_filter(
            $activities,
            function (Activity $activity) {
                return null !== $activity->getStartMicroTime() && null !== $activity->getEndMicroTime();
            }
        );
        $this->orderActivities($activities);
        $nbActivities = count($activities);

        for ($i = 0; $i < $nbActivities; $i++) {
            $activity = $activities[$i];
            $nextActivity = $activities[$i + 1] ?? null;

            $from = $activity->getStartMicroTime() - $firstTime;
            if ($nextActivity) {
                $to = $nextActivity->getStartMicroTime() - $firstTime;

                if ($activity->getEndMicroTime() > $nextActivity->getStartMicroTime()) {
                    $memory_usage = $nextActivity->getStartMemoryUsage();
                    $memory_peak_usage = $nextActivity->getStartMemoryPeakUsage();
                } else {
                    $memory_usage = $activity->getEndMemoryUsage();
                    $memory_peak_usage = $activity->getEndMemoryPeakUsage();
                }
            } else {
                $to = $activity->getEndMicroTime() - $firstTime;
                $memory_usage = $activity->getEndMemoryUsage();
                $memory_peak_usage = $activity->getEndMemoryPeakUsage();
            }

            $memoryUsages[] = [
                'from' => $from,
                'to' => $to,
                'memory' => $memory_usage,
                'memory_peak' => $memory_peak_usage,
            ];
        }

        return $memoryUsages;
    }

    /**
     * Get memory peak usage.
     *
     * @param string|null $group Group name, null for all
     *
     * @return int|null
     */
    public function getMemoryPeakUsage(string $group = null): ?int
    {
        $activities = $this->getActivities($group);
        $activities = array_map(
            function (Activity $activity) {
                return $activity->getEndMemoryPeakUsage();
            },
            $activities
        );
        $activities = array_filter($activities);

        if (empty($activities)) {
            return null;
        }

        return max($activities);
    }
}