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

use Berlioz\Config\ConfigAwareInterface;
use Berlioz\Config\ConfigAwareTrait;
use Berlioz\Config\ConfigInterface;
use Berlioz\Config\Exception\ConfigException;
use Berlioz\Core\Exception\AssetException;

/**
 * Class Assets.
 *
 * @package Berlioz\Core\Asset
 */
class Assets implements ConfigAwareInterface
{
    use ConfigAwareTrait;

    /** @var Manifest Manifest */
    private $manifest;
    /** @var EntryPoints Entry points file */
    private $entryPoints;

    /**
     * Assets constructor.
     *
     * @param ConfigInterface $config
     */
    public function __construct(ConfigInterface $config)
    {
        $this->setConfig($config);
    }

    /**
     * Get manifest.
     *
     * @return Manifest
     * @throws ConfigException
     * @throws AssetException
     */
    public function getManifest(): Manifest
    {
        if (null === $this->manifest) {
            $this->manifest = new Manifest($this->getConfig()->get('berlioz.assets.manifest'));
        }

        return $this->manifest;
    }

    /**
     * Get entry points.
     *
     * @return EntryPoints
     * @throws ConfigException
     * @throws AssetException
     */
    public function getEntryPoints(): EntryPoints
    {
        if (null === $this->entryPoints) {
            $this->entryPoints =
                new EntryPoints(
                    $this->getConfig()->get('berlioz.assets.entrypoints'),
                    $this->getConfig()->get('berlioz.assets.entrypoints_key')
                );
        }

        return $this->entryPoints;
    }
}