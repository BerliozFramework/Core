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

namespace Berlioz\Core\Package;

use Berlioz\Core\Core;
use Berlioz\Core\CoreAwareTrait;
use Berlioz\ServiceContainer\Service;

/**
 * Class AbstractPackage.
 *
 * @package Berlioz\Core\Package
 */
abstract class AbstractPackage implements PackageInterface
{
    use CoreAwareTrait;

    ///////////////
    /// PACKAGE ///
    ///////////////

    /**
     * @inheritdoc
     */
    public static function config()
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public static function register(Core $core): void
    {
    }

    /**
     * Add new service to the service container.
     *
     * @param Core $core
     * @param Service $service
     *
     * @return void
     */
    protected static function addService(Core $core, Service $service): void
    {
        $core->getServiceContainer()->add($service);
    }

    /**
     * @inheritdoc
     */
    public function init(): void
    {
    }
}