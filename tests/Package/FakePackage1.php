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

use Berlioz\Core\Core;
use Berlioz\Core\Package\AbstractPackage;
use Berlioz\ServiceContainer\Service;

class FakePackage1 extends AbstractPackage
{
    public static $foo = false;

    public static function config()
    {
        return realpath(__DIR__ . '/fake.package1.config.json');
    }

    public static function register(Core $core): void
    {
        self::addService($core, new Service(\DateTime::class, 'date'));
    }

    public function init(): void
    {
        self::$foo = true;
    }
}