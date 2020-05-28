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

namespace Berlioz\Core\TestProject;

use Berlioz\Core\Core;
use Berlioz\Core\CoreAwareInterface;
use Berlioz\Core\CoreAwareTrait;

class ServiceBar implements CoreAwareInterface
{
    use CoreAwareTrait;
    public $quux;

    public function __construct(Core $core, string $quux)
    {
        $this->core = $core;
        $this->quux = $quux;
    }
}