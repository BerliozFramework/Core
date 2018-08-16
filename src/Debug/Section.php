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

namespace Berlioz\Core\Debug;

interface Section extends \Serializable
{
    /**
     * Get string representation of debug section.
     *
     * @return string
     */
    public function __toString(): string;

    /**
     * Save report.
     *
     * @return mixed
     */
    public function saveReport();

    /**
     * Get section id.
     *
     * @return string
     */
    public function getSectionId(): string;

    /**
     * Get section name.
     *
     * @return string
     */
    public function getSectionName(): string;
}