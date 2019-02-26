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

namespace Berlioz\Core\Package;

use Berlioz\Config\ExtendedJsonConfig;
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

    /////////////////////
    /// SERIALIZATION ///
    /////////////////////

    /**
     * @inheritdoc
     */
    final public function serialize()
    {
        return serialize([]);
    }

    /**
     * @inheritdoc
     */
    final public function unserialize($serialized)
    {
    }

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
     * @param \Berlioz\Core\Core                $core
     * @param \Berlioz\ServiceContainer\Service $service
     *
     * @return void
     * @throws \Berlioz\Core\Exception\BerliozException
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