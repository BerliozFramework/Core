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

namespace Berlioz\Core\Tests\Container\Provider;

use Berlioz\Core\Container\Provider\AppServiceProvider;
use Berlioz\Core\Container\Provider\CoreServiceProvider;
use Berlioz\Core\Core;
use Berlioz\Core\Tests\Directories\FakeDefaultDirectories;
use Berlioz\ServiceContainer\Provider\ProviderTestCase;

class ServiceProviderTest extends ProviderTestCase
{
    private static Core $core;

    protected static function getCore(): Core
    {
        return self::$core ?? self::$core = new Core(new FakeDefaultDirectories(), false);
    }

    /**
     * @inheritDoc
     */
    public static function providers(): array
    {
        return [
            [new AppServiceProvider(self::getCore())],
            [new CoreServiceProvider(self::getCore())]
        ];
    }
}
