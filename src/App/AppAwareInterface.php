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

namespace Berlioz\Core\App;

interface AppAwareInterface
{
    /**
     * Get application.
     *
     * @return \Berlioz\Core\App\AbstractApp|null
     */
    public function getApp(): ?AbstractApp;

    /**
     * Set application.
     *
     * @param \Berlioz\Core\App\AbstractApp $app
     *
     * @return static
     */
    public function setApp(AbstractApp $app);

    /**
     * Has application ?
     *
     * @return bool
     */
    public function hasApp(): bool;
}