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

namespace Berlioz\Core\Tests\Asset;

use Berlioz\Config\ConfigInterface;
use Berlioz\Config\JsonConfig;
use Berlioz\Core\Asset\Assets;
use Berlioz\Core\Asset\EntryPoints;
use Berlioz\Core\Asset\Manifest;
use PHPUnit\Framework\TestCase;

class AssetsTest extends TestCase
{
    private function getConfig(): ConfigInterface
    {
        return new JsonConfig(
            '{
    "berlioz": {
        "assets": {
            "manifest": "' . addslashes(realpath(__DIR__ . '/../_files/manifest.json')) . '",
            "entrypoints": "' . addslashes(realpath(__DIR__ . '/../_files/entrypoints.target.json')) . '",
            "entrypoints_key": "entrypoints"
        }
    }
}'
        );
    }

    public function testGetEntryPoints()
    {
        $assets = new Assets($this->getConfig());

        $this->assertInstanceOf(EntryPoints::class, $entryPoints = $assets->getEntryPoints());
        $this->assertSame($entryPoints, $assets->getEntryPoints());
    }

    public function testGetManifest()
    {
        $assets = new Assets($this->getConfig());

        $this->assertInstanceOf(Manifest::class, $manifest = $assets->getManifest());
        $this->assertSame($manifest, $assets->getManifest());
    }
}
