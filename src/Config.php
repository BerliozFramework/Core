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

namespace Berlioz\Core;

use Berlioz\Config\ExtendedJsonConfig;
use Berlioz\Core\Exception\ConfigException;

class Config extends ExtendedJsonConfig
{
    /**
     * Extends JSON.
     *
     * @param string $json      JSON data
     * @param bool   $jsonIsUrl If JSON data is URL? (default: false)
     * @param bool   $isParent  If is parent JSON to extends (default: true)
     *
     * @return static
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function extendsJson(string $json, bool $jsonIsUrl = false, bool $isParent = true): ExtendedJsonConfig
    {
        try {
            $config = $this->load($json, $jsonIsUrl);

            return $this->extendsArray($config, $isParent);
        } catch (\Throwable $e) {
            throw new ConfigException('Configuration error', 0, $e);
        }
    }

    /**
     * Extends array.
     *
     * @param array $config   Configuration
     * @param bool  $isParent If is parent config to extends (default: true)
     *
     * @return static
     */
    public function extendsArray(array $config, bool $isParent = true): ExtendedJsonConfig
    {
        if ($isParent) {
            $this->configuration = array_replace_recursive($config, $this->configuration);
        } else {
            $this->configuration = array_replace_recursive($this->configuration, $config);
        }

        return $this;
    }
}