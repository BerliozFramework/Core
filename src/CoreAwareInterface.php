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

namespace Berlioz\Core;

/**
 * Interface CoreAwareInterface.
 *
 * @package Berlioz\Core
 */
interface CoreAwareInterface
{
    /**
     * Get core.
     *
     * @return \Berlioz\Core\Core|null
     */
    public function getCore(): ?Core;

    /**
     * Set core.
     *
     * @param \Berlioz\Core\Core $core
     *
     * @return static
     */
    public function setCore(Core $core);
}