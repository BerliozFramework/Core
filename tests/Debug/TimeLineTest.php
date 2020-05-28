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

namespace Berlioz\Core\Tests\Debug;

use Berlioz\Core\Debug\Activity;
use Berlioz\Core\Debug\TimeLine;
use PHPUnit\Framework\TestCase;

class TimeLineTest extends TestCase
{
    public function test()
    {
        $timeLine = new TimeLine();

        $this->assertEquals('Activities', $timeLine->getSectionName());
        $this->assertEquals('activities', $timeLine->getSectionId());
        $this->assertCount(0, $timeLine);

        $timeLine->addActivity($activity1 = new Activity('Foo', 'Qux'));
        $timeLine->addActivity($activity2 = new Activity('Bar'));
        $timeLine->addActivity($activity3 = new Activity('Bar'));

        $this->assertCount(3, $timeLine);
        $this->assertCount(3, $timeLine->getActivities());
        $this->assertCount(2, $timeLine->getActivities('Application'));
        $this->assertCount(1, $timeLine->getActivities('Qux'));

        $this->assertEquals(['Qux', 'Application'], $timeLine->getGroups());
        $this->assertNull($timeLine->getDuration());
        $this->assertNull($timeLine->getFirstTime());
        $this->assertNull($timeLine->getLastTime());
        $this->assertNull($timeLine->getMemoryPeakUsage());
        $this->assertEmpty($timeLine->getMemoryUsages());

        $activity1->start(10000);

        $this->assertNull($timeLine->getDuration());

        $activity2->start(15000);
        $activity2->end(19000);
        $activity1->end(20000);

        $this->assertEquals(10000, $timeLine->getDuration());
        $this->assertEquals(10000, $timeLine->getDuration('Qux'));
        $this->assertEquals(4000, $timeLine->getDuration('Application'));

        $this->assertNotNull($timeLine->getMemoryPeakUsage());
        $this->assertEquals($activity1->getEndMemoryPeakUsage(), $timeLine->getMemoryPeakUsage());
        $this->assertCount(2, $timeLine->getMemoryUsages());

        $this->assertEquals(
            max($activity2->getEndMemoryPeakUsage(), $activity1->getEndMemoryPeakUsage()),
            $timeLine->getMemoryPeakUsage()
        );

        $this->assertEquals(var_export($timeLine->getActivities(), true), (string)$timeLine);
        $this->assertEquals(unserialize(serialize($timeLine)), $timeLine);
    }
}
