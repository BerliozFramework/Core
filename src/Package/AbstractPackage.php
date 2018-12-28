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

use Berlioz\Core\CoreAwareTrait;
use Berlioz\ServiceContainer\Service;

/**
 * Class AbstractPackage.
 *
 * @package Berlioz\Core\Package
 */
abstract class AbstractPackage implements PackageInterface, \Serializable
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
    public function register()
    {
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
    }

    /**
     * Add new service to the service container.
     *
     * @param \Berlioz\ServiceContainer\Service $service
     *
     * @return static
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    protected function addService(Service $service): AbstractPackage
    {
        $this->getCore()->getServiceContainer()->add($service);

        return $this;
    }

    /**
     * Merge configuration.
     *
     * @param string $configFileName
     *
     * @return static
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    protected function mergeConfig(string $configFileName): AbstractPackage
    {
        $this->getCore()->getConfig()->extendsJson($configFileName, true, true);

        return $this;
    }
}