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

use Berlioz\Core\Asset\JsonAsset;
use Berlioz\Core\Asset\Manifest;
use Berlioz\Core\Exception\AssetException;
use PHPUnit\Framework\TestCase;

class JsonAssetTest extends TestCase
{
    public function test__construct()
    {
        $jsonAsset = $this->getMockForAbstractClass(JsonAsset::class, [__DIR__ . '/../_files/manifest.json']);
        $this->assertInstanceOf(JsonAsset::class, $jsonAsset);
    }

    public function test__constructBadJson()
    {
        $this->expectException(AssetException::class);
        $jsonAsset = $this->getMockForAbstractClass(Manifest::class, [__DIR__ . '/../_files/manifest.bad.json']);
    }

    public function test__constructJsonNotExists()
    {
        $this->expectException(AssetException::class);
        $jsonAsset = $this->getMockForAbstractClass(JsonAsset::class, [__DIR__ . '/../_files/manifest.notexists.json']);
    }
}
