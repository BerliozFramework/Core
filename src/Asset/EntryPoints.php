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
 * Class EntryPoints.
 *
 * @package Berlioz\Core\Asset
 */
class EntryPoints extends JsonAsset
{
    /** @var string|null Target to get entry points */
    private $target;

    /**
     * EntryPoints constructor.
     *
     * @param string      $filename Filename
     * @param string|null $target   Target to get entry points
     *
     * @throws \Berlioz\Core\Exception\AssetException
     */
    public function __construct(string $filename, ?string $target = null)
    {
        parent::__construct($filename);
        $this->target = $target;

        if (!empty($this->target)) {
            try {
                if (is_null($this->assets = b_array_traverse($this->assets, explode('.', $this->target)))) {
                    throw new AssetException(sprintf('Key "%s" to target entry points is invalid', $this->target));
                }
            } catch (AssetException $e) {
                throw $e;
            } catch (\Exception $e) {
                throw new AssetException(sprintf('Error to target key "%s" of entry points', $this->target), 0, $e);
            }
        }
    }

    /**
     * Get asset for given entry name and file type.
     *
     * @param string      $entry
     * @param string|null $type
     *
     * @return array
     */
    public function get(string $entry, ?string $type = null): array
    {
        if (!isset($this->assets[$entry])) {
            return [];
        }

        if (is_null($type)) {
            $assets = $this->assets[$entry];

            array_walk($assets,
                function (&$value) {
                    if (!is_array($value)) {
                        $value = [$value];
                    }
                });

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