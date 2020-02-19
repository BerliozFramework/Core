<?php
/**
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2017 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

declare(strict_types=1);

namespace Berlioz\Core\Asset;

use Berlioz\Core\Exception\AssetException;

/**
 * Class Manifest.
 *
 * @package Berlioz\Core\Asset
 */
class Manifest extends JsonAsset
{
    protected const JSON_DEPTH = 2;

    /**
     * Has asset?
     *
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->assets);
    }

    /**
     * Get asset.
     *
     * @param string $key
     *
     * @return string
     * @throws \Berlioz\Core\Exception\AssetException
     */
    public function get(string $key): string
    {
        if (!$this->has($key)) {
            throw new AssetException(sprintf('Asset "%s" does not exists in manifest', $key));
        }

        return $this->assets[$key];
    }
}