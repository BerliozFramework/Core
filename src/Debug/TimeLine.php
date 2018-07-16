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

class TimeLine extends AbstractSection implements \Countable
{
    /** @var \Berlioz\Core\Debug\Activity[] Activities */
    private $activities;

    /**
     * TimeLine constructor.
     */
    public function __construct()
    {
        $this->activities = [];
    }

    /**
     * @inheritDoc
     */
    public function count()
    {
        return count($this->activities);
    }

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        return serialize($this->activities);
    }

    /**
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        $this->activities = unserialize($serialized);
    }

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
     * @param \Berlioz\Core\Debug\Activity[] $activities
     * @param string                         $orderBy Order by? Values: 'start', 'end' or 'duration'
     */
    private function orderActivities(array &$activities, string $orderBy = 'start')
    {
        usort(
            $activities,
            function ($a, $b) use ($orderBy) {
                /**
                 * @var \Berlioz\Core\Debug\Activity $a
                 * @var \Berlioz\Core\Debug\Activity $b
                 */
                switch ($orderBy) {
                    case 'duration':
                        $aTime = $a->duration();
                        $bTime = $b->duration();
                        break;
                    case 'end':
                        $aTime = $a->getEndMicroTime();
                        $bTime = $b->getEndMicroTime();
                        break;
                    default:
                        $aTime = $a->getStartMicroTime();
                        $bTime = $b->getStartMicroTime();
                }

                return $aTime == $bTime ? 0 : (($aTime < $bTime) ? -1 : 1);
            }
        );
    }

    /**
     * Add activity.
     *
     * @param \Berlioz\Core\Debug\Activity $activity
     *
     * @return \Berlioz\Core\Debug\TimeLine
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
     * @return \Berlioz\Core\Debug\Activity[]
     */
    public function getActivities(string $group = null): array
    {
        $this->orderActivities($this->activities);
        $activities = [];

        foreach ($this->activities as $activity) {
            if (is_null($group) || $activity->getGroup() == $group) {
                $activities[] = $activity;
            }
        }

        return $activities;
    }

    /**
     * Get first time.
     *
     * @param string|null $group Group name, null for all
     *
     * @return float
     */
    public function getFirstTime(string $group = null): float
    {
        $firstTime = 0;

        foreach ($this->activities as $activity) {
            if ((is_null($group) || $activity->getGroup() == $group) &&
                ($activity->getStartMicroTime() < $firstTime || $firstTime == 0)) {
                $firstTime = $activity->getStartMicroTime();
            }
        }

        return $firstTime;
    }

    /**
     * Get last time.
     *
     * @param string|null $group Group name, null for all
     *
     * @return float
     */
    public function getLastTime(string $group = null): float
    {
        $lastTime = 0;

        foreach ($this->activities as $activity) {
            if ((is_null($group) || $activity->getGroup() == $group) &&
                ($activity->getEndMicroTime() > $lastTime || $lastTime == 0)) {
                $lastTime = $activity->getEndMicroTime();
            }
        }

        return $lastTime;
    }

    /**
     * Get duration.
     *
     * @param string|null $group Group name, null for all
     *
     * @return float
     */
    public function getDuration(string $group = null): float
    {
        return $this->getLastTime($group) - $this->getFirstTime($group);
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

        /** @var \Berlioz\Core\Debug\Activity[] $activities */
        $activities = $this->getActivities($group);
        $this->orderActivities($activities, 'start');
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

            $memoryUsages[] = ['from'        => $from,
                               'to'          => $to,
                               'memory'      => $memory_usage,
                               'memory_peak' => $memory_peak_usage];
        }

        return $memoryUsages;
    }

    /**
     * Get memory peak usage.
     *
     * @param string|null $group Group name, null for all
     *
     * @return int
     */
    public function getMemoryPeakUsage(string $group = null): int
    {
        $memoryUsage = 0;

        foreach ($this->activities as $activity) {
            if ((is_null($group) || $activity->getGroup() == $group) &&
                ($activity->getEndMemoryPeakUsage() > $memoryUsage || $memoryUsage == 0)) {
                $memoryUsage = $activity->getEndMemoryPeakUsage();
            }
        }

        return $memoryUsage;
    }
}