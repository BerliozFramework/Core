<?php
/*
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2021 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

namespace Berlioz\Core\Tests;

use Berlioz\Core\Core;
use Berlioz\Core\CoreAwareTrait;
use Berlioz\Core\Tests\Directories\FakeDefaultDirectories;
use PHPUnit\Framework\TestCase;

class CoreAwareTraitTest extends TestCase
{
    public function test()
    {
        /** @var CoreAwareTrait $trait */
        $trait = $this->getMockForTrait(CoreAwareTrait::class);

        $this->assertNull($trait->getCore());

        $trait->setCore($core = new Core(new FakeDefaultDirectories(), false));

        $this->assertSame($trait->getCore(), $core);
    }
}
