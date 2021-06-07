<?php
/*
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2021 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

declare(strict_types=1);

namespace Berlioz\Core\Asset;

use Berlioz\Core\Exception\AssetException;

/**
 * Class EntryPoints.
 */
class EntryPoints extends JsonAsset
{
    /**
     * EntryPoints constructor.
     *
     * @param string $filename Filename
     * @param string|null $target Target to get entry points
     *
     * @throws AssetException
     */
    public function __construct(
        string $filename,
        protected ?string $target = null
    ) {
        parent::__construct($filename);

        if (!empty($this->target)) {
            try {
                if (null === ($assets = b_array_traverse_get($this->assets, $this->target))) {
                    throw new AssetException(sprintf('Key "%s" to target entry points is invalid', $this->target));
                }

                $this->assets = (array)$assets;
            } catch (AssetException $exception) {
                throw $exception;
            }
        }
    }

    /**
     * Get asset for given entry name and file type.
     *
     * @param string $entry
     * @param string|null $type
     *
     * @return array
     */
    public function get(string $entry, ?string $type = null): array
    {
        if (!isset($this->assets[$entry])) {
            return [];
        }

        if (null === $type) {
            $assets = $this->assets[$entry];

            array_walk(
                $assets,
                function (&$value) {
                    if (!is_array($value)) {
                        $value = [$value];
                    }
                }
            );

            return $assets;
        }

        if (!isset($this->assets[$entry][$type])) {
            return [];
        }

        $assets = $this->assets[$entry][$type];

        if (!is_array($assets)) {
            $assets = [$assets];
        }

        return $assets;
    }
}