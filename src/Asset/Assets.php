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
 * Class Assets.
 */
class Assets
{
    private ?Manifest $manifest = null;
    private ?EntryPoints $entryPoints = null;

    /**
     * Assets constructor.
     *
     * @param string|null $manifestFile
     * @param string|null $entryPointsFile
     * @param string|null $entryPointsKey
     *
     * @throws AssetException
     */
    public function __construct(
        ?string $manifestFile = null,
        ?string $entryPointsFile = null,
        ?string $entryPointsKey = null,
    ) {
        if (null !== $manifestFile) {
            $this->manifest = new Manifest($manifestFile);
        }

        if (null !== $entryPointsFile) {
            $this->entryPoints = new EntryPoints($entryPointsFile, $entryPointsKey);
        }
    }

    /**
     * Get manifest.
     *
     * @return Manifest|null
     */
    public function getManifest(): ?Manifest
    {
        return $this->manifest;
    }

    /**
     * Get entry points.
     *
     * @return EntryPoints|null
     */
    public function getEntryPoints(): ?EntryPoints
    {
        return $this->entryPoints;
    }
}