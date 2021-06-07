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

use Berlioz\Core\Directories\DirectoriesInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Class CacheManager.
 */
class CacheManager implements CacheInterface
{
    protected string|false $class;
    protected CacheInterface $cache;

    public function __construct(CacheInterface|bool $cache, DirectoriesInterface $directories)
    {
        $this->cache =
            (function (CacheInterface|bool $cache) use ($directories) {
                if (false === $cache) {
                    $this->class = false;

                    return new MemoryCacheDriver();
                }

                if (true === $cache) {
                    $this->class = FileCacheDriver::class;

                    return new FileCacheDriver($directories);
                }

                $this->class = get_class($cache);

                return $cache;
            })(
                $cache
            );
    }

    /**
     * Get class of cache adapter or false if disable.
     *
     * @return string|false
     */
    public function getClass(): string|false
    {
        return $this->class;
    }

    /**
     * @inheritDoc
     */
    public function get($key, $default = null): mixed
    {
        return $this->cache->get($key, $default);
    }

    /**
     * @inheritDoc
     */
    public function set($key, $value, $ttl = null): bool
    {
        return $this->cache->set($key, $value, $ttl);
    }

    /**
     * @inheritDoc
     */
    public function delete($key): bool
    {
        return $this->cache->delete($key);
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        return $this->cache->clear();
    }

    /**
     * @inheritDoc
     */
    public function getMultiple($keys, $default = null): iterable
    {
        return $this->cache->getMultiple($keys, $default);
    }

    /**
     * @inheritDoc
     */
    public function setMultiple($values, $ttl = null): bool
    {
        return $this->cache->setMultiple($values, $ttl);
    }

    /**
     * @inheritDoc
     */
    public function deleteMultiple($keys): bool
    {
        return $this->cache->deleteMultiple($keys);
    }

    /**
     * @inheritDoc
     */
    public function has($key)
    {
        return $this->cache->has($key);
    }
}