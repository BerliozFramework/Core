<?php
/**
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2020 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

declare(strict_types=1);

namespace Berlioz\Core\Asset;

use Berlioz\Core\Exception\AssetException;

/**
 * Class JsonAsset.
 *
 * @package Berlioz\Core\Asset
 */
abstract class JsonAsset
{
    protected const JSON_DEPTH = 512;
    /** @var string Filename */
    protected $filename;
    /** @var array Assets */
    protected $assets = [];

    /**
     * JsonAsset constructor.
     *
     * @param string $filename
     *
     * @throws AssetException
     */
    public function __construct(string $filename)
    {
        $this->filename = $filename;
        $this->reload();
    }

    /**
     * Reload.
     *
     * @throws AssetException
     */
    public function reload()
    {
        if (!file_exists($this->filename)) {
            throw new AssetException(sprintf('Assets file "%s" does not exists', $this->filename));
        }

        // Get content of assets file
        if (($assets = @file_get_contents($this->filename)) === false) {
            throw new AssetException(sprintf('Assets file "%s" is not readable', $this->filename));
        }

        // JSON decode of assets content
        if (!is_array($assets = @json_decode($assets, true, static::JSON_DEPTH))) {
            throw new AssetException(sprintf('Assets file "%s" is not a valid JSON file', $this->filename));
        }

        // Standardize directory separator
        $standardizeSeparator =
            function (&$value) {
                $value = str_replace('\\', '/', $value);
            };
        $keys = (array)array_keys($assets);
        $values = (array)array_values($assets);
        array_walk_recursive($keys, $standardizeSeparator);
        array_walk_recursive($values, $standardizeSeparator);
        $assets = array_combine($keys, (array)$values);
        unset($keys, $values);

        $this->assets = $assets;
    }
}