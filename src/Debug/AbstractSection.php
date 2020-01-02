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

/**
 * Class AbstractSection.
 *
 * @package Berlioz\Core\Debug
 */
abstract class AbstractSection implements Section
{
    /**
     * @inheritdoc
     */
    public function saveReport()
    {
    }

    /**
     * @inheritdoc
     */
    public function getSectionId(): string
    {
        $name = mb_strtolower($this->getSectionName());
        $name = preg_replace('$[^\w\-_]$i', '', $name);
        $name = preg_replace(['$\s{2,}$i', '$-{2,}$i', '$_{2,}$i'], [' ', '-', '_'], $name);

        return $name;
    }
}