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

namespace Berlioz\Core\Tests\App;

use Berlioz\Core\Core;
use Berlioz\Core\Tests\Directories\FakeDefaultDirectories;
use PHPUnit\Framework\TestCase;

class AppAwareTraitTest extends TestCase
{
    public function test()
    {
        $app = new FakeApp(new Core(new FakeDefaultDirectories(), false));
        $appAware = new FakeAppAwareTrait();

        $this->assertFalse($appAware->hasApp());
        $this->assertNull($appAware->getApp());

        $appAware->setApp($app);

        $this->assertSame($appAware->getApp(), $app);
        $this->assertTrue($appAware->hasApp());
    }
}
