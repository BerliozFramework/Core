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

namespace Berlioz\Core\Cache;

use Berlioz\Core\Exception\InvalidArgumentCacheException;
use Psr\SimpleCache\CacheInterface;

/**
 * Class NullCacheDriver.
 */
class NullCacheDriver implements CacheInterface
{
    use KeyControlTrait;

    /**
     * @inheritDoc
     */
    public function get($key, $default = null)
    {
        $this->controlKey($key);

        return $default;
    }

    /**
     * @inheritDoc
     */
    public function set($key, $value, $ttl = null)
    {
        $this->controlKey($key);

        return true;
    }

    /**
     * @inheritDoc
     */
    public function delete($key)
    {
        $this->controlKey($key);

        return true;
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getMultiple($keys, $default = null)
    {
        if (!is_iterable($keys)) {
            throw new InvalidArgumentCacheException('First argument must be iterable');
        }

        $this->controlKeys((array)$keys);

        return array_fill_keys((array)$keys, $default);
    }

    /**
     * @inheritDoc
     */
    public function setMultiple($values, $ttl = null)
    {
        if (!is_iterable($values)) {
            throw new InvalidArgumentCacheException('First argument must be iterable');
        }

        $this->controlKeys(array_keys((array)$values));

        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteMultiple($keys)
    {
        if (!is_iterable($keys)) {
            throw new InvalidArgumentCacheException('First argument must be iterable');
        }

        $this->controlKeys((array)$keys);

        return true;
    }

    /**
     * @inheritDoc
     */
    public function has($key)
    {
        $this->controlKey($key);

        return false;
    }
}