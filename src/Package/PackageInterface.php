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

use Berlioz\Core\CoreAwareInterface;

/**
 * Interface PackageInterface.
 *
 * @package Berlioz\Core\Package
 */
interface PackageInterface extends CoreAwareInterface
{
    /**
     * Get default config filename of package.
     *
     * Must return null if no default config file.
     *
     * @return string|null
     */
    //public static function getDefaultConfigFilename(): ?string;

    /**
     * Register package.
     *
     * Method called for the registration of all packages.
     * Do not use this method to do any actions on framework, only configuration and registration of services.
     *
     * @return mixed
     */
    public function register();

    /**
     * Init package.
     *
     * Method called after creation of all packages.
     *
     * @return mixed
     */
    public function init();
}