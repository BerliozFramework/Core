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

use Berlioz\Core\Asset\Assets;
use Berlioz\Core\Core;
use Berlioz\Core\Tests\Directories\FakeDefaultDirectories;
use PHPUnit\Framework\TestCase;

class AbstractAppTest extends TestCase
{
    public function test()
    {
        $app = new FakeApp($core = new Core(new FakeDefaultDirectories(), false));

        $this->assertSame($app->getCore(), $core);
        $this->assertSame($app->getService(Core::class), $core);
        $this->assertInstanceOf(Assets::class, $app->getAssets());
        $this->assertSame($app->getAssets(), $app->getService(Assets::class));
    }
}
