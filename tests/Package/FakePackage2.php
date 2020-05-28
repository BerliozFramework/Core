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

namespace Berlioz\Core\Tests\Package;

use Berlioz\Config\JsonConfig;
use Berlioz\Core\Core;
use Berlioz\Core\Package\AbstractPackage;
use Berlioz\ServiceContainer\Service;

class FakePackage2 extends AbstractPackage
{
    public static function config()
    {
        return new JsonConfig('{"package2": "qux"}');
    }

    public static function register(Core $core): void
    {
        $service = new Service(\stdClass::class, 'foo');
        $service->setFactory(static::class . '::factory');
    }

    public static function factory()
    {
        $obj = new \stdClass();
        $obj->foo = 'bar';

        return $obj;
    }
}