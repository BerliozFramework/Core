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

namespace Berlioz\Core\Debug;

use Countable;

/**
 * Class PhpError.
 *
 * @package Berlioz\Core\Debug
 */
class PhpError extends AbstractSection implements Countable
{
    /** @var array PHP errors */
    private $phpErrors = [];

    /////////////////////////
    /// SECTION INTERFACE ///
    /////////////////////////

    public function __serialize(): array
    {
        return $this->phpErrors;
    }

    public function __unserialize(array $data): void
    {
        $this->phpErrors = $data;
    }

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        return serialize($this->__serialize());
    }

    /**
     * @inheritdoc
     */
    public function unserialize($serialized): void
    {
        $unserialized = unserialize($serialized);
        $this->__unserialize($unserialized);
    }

    /**
     * @inheritdoc
     */
    public function __toString(): string
    {
        return var_export($this->phpErrors, true);
    }

    /**
     * @inheritdoc
     */
    public function getSectionName(): string
    {
        return 'PHP Errors';
    }

    ///////////////////////////
    /// COUNTABLE INTERFACE ///
    ///////////////////////////

    /**
     * @inheritdoc
     */
    public function count(): int
    {
        return count($this->phpErrors);
    }

    ////////////////////
    /// USER DEFINED ///
    ////////////////////

    /**
     * Handle.
     *
     * @return PhpError
     */
    public function handle(): PhpError
    {
        set_error_handler([$this, 'phpErrorHandler']);

        return $this;
    }

    /**
     * PHP error handler function
     *
     * @param int $errno The level of the error raised
     * @param string $message The error message
     * @param string $file The filename that the error was raised in
     * @param int $line The line number the error was raised at
     *
     * @return false
     */
    public function phpErrorHandler(int $errno, string $message, ?string $file = null, ?int $line = null)
    {
        $this->phpErrors[] = [
            'errno' => $errno,
            'message' => $message,
            'file' => $file,
            'line' => $line,
        ];

        return false;
    }

    /**
     * Get PHP errors.
     *
     * @return array
     */
    public function getPhpErrors(): array
    {
        return $this->phpErrors ?? [];
    }
}