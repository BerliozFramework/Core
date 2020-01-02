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

use Berlioz\Core\Core;
use Berlioz\Core\CoreAwareInterface;
use Serializable;

/**
 * Interface PackageInterface.
 *
 * @package Berlioz\Core\Package
 */
interface PackageInterface extends CoreAwareInterface, Serializable
{
    /**
     * Package configuration.
     *
     * Method called for the configuration of package.
     * Do not use this method to do any actions on framework, only configuration of package.
     *
     * @return \Berlioz\Config\ConfigInterface|array|string|null
     */
    public static function config();

    /**
     * Register package.
     *
     * Method called for the registration of services associated to the package.
     * Do not use this method to do any actions on framework, only registration of services.
     *
     * @param \Berlioz\Core\Core $core
     *
     * @return void
     */
    public static function register(Core $core): void;

    /**
     * Init package.
     *
     * Method called after creation of all packages.
     *
     * @return void
     */
    public function init(): void;
}