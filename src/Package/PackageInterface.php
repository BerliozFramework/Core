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

/**
 * Interface PackageInterface.
 *
 * @package Berlioz\Core\Package
 */
interface PackageInterface
{
    /**
     * Get default config filename of package.
     *
     * @return string|null
     */
    public function getDefaultConfigFilename(): ?string;

    /**
     * Init package.
     *
     * Method called after creation of all packages.
     *
     * @return mixed
     */
    public function init();
}