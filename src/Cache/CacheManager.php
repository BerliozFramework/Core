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

namespace Berlioz\Core\Cache;

use Berlioz\Core\Directories\DirectoriesInterface;
use Berlioz\Core\Exception\CacheException;
use Berlioz\Core\Exception\InvalidArgumentCacheException;
use DateInterval;
use DateTime;
use Exception;
use Psr\SimpleCache\CacheInterface;
use Traversable;

/**
 * Class CacheManager.
 *
 * @package Berlioz\Core\Cache
 */
class CacheManager implements CacheInterface
{
    use KeyControlTrait;

    public const CACHE_DIRECTORY = 'berlioz';
    /** @var DirectoriesInterface */
    private $directories;

    /**
     * CacheManager constructor.
     *
     * @param DirectoriesInterface $directories
     */
    public function __construct(DirectoriesInterface $directories)
    {
        $this->directories = $directories;
    }

    /**
     * Is valid TTL?
     *
     * @param mixed $ttl
     *
     * @return bool
     * @throws Exception
     */
    private function isValidTtl($ttl): bool
    {
        if (null === $ttl) {
            return true;
        }

        if ($ttl instanceof DateTime && $ttl > new DateTime('now')) {
            return true;
        }

        return false;
    }

    /**
     * Get filename from name.
     *
     * @param string $name
     *
     * @return string|null
     */
    private function getFilename(string $name): ?string
    {
        $name = md5($name);

        return
            $this->directories->getCacheDir() .
            DIRECTORY_SEPARATOR .
            static::CACHE_DIRECTORY .
            DIRECTORY_SEPARATOR .
            substr($name, 0, 2) .
            DIRECTORY_SEPARATOR .
            $name .
            '.txt';
    }

    /**
     * Get cache file contents.
     *
     * @param string $filename
     *
     * @return array|null
     * @throws CacheException
     */
    private function getCacheFileContents(string $filename): ?array
    {
        if (!file_exists($filename)) {
            return null;
        }

        if (false === ($content = @file_get_contents($filename))) {
            throw new CacheException(sprintf('Unable to read file from cache "%s"', $filename));
        }

        if (false === ($unserialized = @unserialize($content)) || !is_array($unserialized)) {
            throw new CacheException(sprintf('Corrupted data in cache file "%s"', $filename));
        }

        return $unserialized;
    }

    /**
     * @inheritdoc
     * @throws CacheException
     */
    public function get($key, $default = null)
    {
        $this->controlKey($key);
        $cacheFilename = $this->getFilename($key);

        if (null === ($content = $this->getCacheFileContents($cacheFilename))) {
            return $default;
        }

        try {
            if ($this->isValidTtl($content['ttl'])) {
                if (($unserializedData = @unserialize($content['data'] ?? null)) === false) {
                    throw new CacheException(
                        sprintf('Corrupted data for key "%s" from cache "%s"', $key, $cacheFilename)
                    );
                }

                return $unserializedData;
            }
        } catch (Exception $e) {
            throw new CacheException('TTL cache exception', 0, $e);
        }

        return $default;
    }

    /**
     * @inheritdoc
     * @throws CacheException
     */
    public function set($key, $data, $ttl = null)
    {
        $this->controlKey($key);
        $cacheFilename = $this->getFilename($key);

        if (($serialized = serialize($data)) === false) {
            throw new CacheException(sprintf('Unable to serialize data to cache save "%s"', $key));
        }

        try {
            if (null !== $ttl) {
                if (is_int($ttl)) {
                    $ttl = new DateInterval(sprintf('PT%dS', $ttl));
                }

                if (!($ttl instanceof DateInterval)) {
                    throw new InvalidArgumentCacheException(sprintf('Not valid TTL for key "%s"', $key));
                }

                $ttl = (new DateTime('now'))->add($ttl);
            }
        } catch (Exception $e) {
            throw new CacheException('TTL cache exception', 0, $e);
        }

        $data = [
            'ttl' => $ttl,
            'data' => $serialized,
        ];

        if (!is_dir($directory = dirname($cacheFilename))) {
            if (@mkdir($directory, 0777, true) === false) {
                throw new CacheException(sprintf('Unable to write cache file "%s"', $cacheFilename));
            }
        }

        if (@file_put_contents($cacheFilename, @serialize($data)) === false) {
            throw new CacheException(sprintf('Unable to save file to cache "%s"', $cacheFilename));
        }

        return true;
    }

    /**
     * @inheritdoc
     * @throws CacheException
     */
    public function delete($key)
    {
        $this->controlKey($key);
        $cacheFilename = $this->getFilename($key);

        if (!file_exists($cacheFilename)) {
            return true;
        }

        if (@unlink($cacheFilename) === true) {
            return true;
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function clear()
    {
        $cacheDir =
            $this->directories->getCacheDir() .
            DIRECTORY_SEPARATOR .
            static::CACHE_DIRECTORY;

        if (is_dir($cacheDir)) {
            return $this->rmdir($cacheDir);
        }

        return true;
    }

    /**
     * Remove recursively directory.
     *
     * @param string $dir
     *
     * @return bool
     */
    private function rmdir(string $dir)
    {
        $dir = rtrim($dir, '\\/');

        if (($files = scandir($dir)) === false) {
            return false;
        }

        $result = true;
        foreach ($files as $file) {
            if (in_array($file, ['.', '..'])) {
                continue;
            }

            // Full filename
            $file = $dir . DIRECTORY_SEPARATOR . $file;

            if (is_dir($file)) {
                if ($this->rmdir($file) === false) {
                    $result = false;
                }
                continue;
            }

            if (@unlink($file) === false) {
                $result = false;
            }
        }

        if ($result) {
            if (@rmdir($dir) === false) {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * @inheritdoc
     * @throws CacheException
     */
    public function getMultiple($keys, $default = null)
    {
        if (!is_iterable($keys)) {
            throw new InvalidArgumentCacheException('First argument must be iterable');
        }

        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    /**
     * @inheritdoc
     * @throws CacheException
     */
    public function setMultiple($values, $ttl = null)
    {
        if (!is_iterable($values)) {
            throw new InvalidArgumentCacheException('First argument must be iterable');
        }

        if (!is_array($values)) {
            /** @var Traversable $values */
            $values = iterator_to_array($values, true);
        }

        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return true;
    }

    /**
     * @inheritdoc
     * @throws CacheException
     */
    public function deleteMultiple($keys)
    {
        if (!is_iterable($keys)) {
            throw new InvalidArgumentCacheException('First argument must be iterable');
        }

        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    /**
     * @inheritdoc
     * @throws CacheException
     */
    public function has($key)
    {
        $this->controlKey($key);
        $cacheFilename = $this->getFilename($key);

        if (null === ($content = $this->getCacheFileContents($cacheFilename))) {
            return false;
        }

        return $this->isValidTtl($content['ttl']);
    }
}