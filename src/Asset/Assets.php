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

namespace Berlioz\Core\Asset;

use Berlioz\Config\ConfigAwareInterface;
use Berlioz\Config\ConfigAwareTrait;
use Berlioz\Core\Config;

/**
 * Class Assets.
 *
 * @package Berlioz\Core\Asset
 */
class Assets implements ConfigAwareInterface
{
    use ConfigAwareTrait;
    /** @var \Berlioz\Core\Asset\Manifest Manifest */
    private $manifest;
    /** @var \Berlioz\Core\Asset\EntryPoints Entry points file */
    private $entrypoints;

    /**
     * Assets constructor.
     *
     * @param \Berlioz\Core\Config $config
     */
    public function __construct(Config $config)
    {
        $this->setConfig($config);
    }

    /**
     * Get manifest.
     *
     * @return \Berlioz\Core\Asset\Manifest
     * @throws \Berlioz\Config\Exception\ConfigException
     * @throws \Berlioz\Core\Exception\AssetException
     */
    public function getManifest(): Manifest
    {
        if (is_null($this->manifest)) {
            $this->manifest = new Manifest($this->getConfig()->get('berlioz.assets.manifest'));
        }

        return $this->manifest;
    }

    /**
     * Get entry points.
     *
     * @return \Berlioz\Core\Asset\EntryPoints
     * @throws \Berlioz\Config\Exception\ConfigException
     * @throws \Berlioz\Core\Exception\AssetException
     */
    public function getEntryPoints(): EntryPoints
    {
        if (is_null($this->entrypoints)) {
            $this->entrypoints = new EntryPoints($this->getConfig()->get('berlioz.assets.entrypoints'),
                                                 $this->getConfig()->get('berlioz.assets.entrypoints_key'));
        }

        return $this->entrypoints;
    }
}