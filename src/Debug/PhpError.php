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

namespace Berlioz\Core\Debug;

class PhpError extends AbstractSection implements \Countable
{
    /** @var array PHP errors */
    private $phpErrors;

    /**
     * PhpError constructor.
     */
    public function __construct()
    {
        $this->phpErrors = [];
    }

    /**
     * @inheritDoc
     */
    public function count()
    {
        return count($this->phpErrors);
    }

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        return serialize($this->phpErrors);
    }

    /**
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        $this->phpErrors = unserialize($serialized);
    }

    /**
     * @inheritdoc
     */
    public function __toString(): string
    {
        return var_export($this->phpErrors, true);
    }

    /**
     * Handle.
     *
     * @return \Berlioz\Core\Debug\PhpError
     */
    public function handle(): PhpError
    {
        set_error_handler([$this, 'phpErrorHandler']);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getSectionName(): string
    {
        return 'PHP Errors';
    }

    /**
     * PHP error handler function
     *
     * @param  int     $errno   The level of the error raised
     * @param  string  $message The error message
     * @param  string  $file    The filename that the error was raised in
     * @param  int     $line    The line number the error was raised at
     * @param  array[] $context Array that points to the active symbol table at the point the error occurred
     *
     * @return false
     */
    public function phpErrorHandler(int $errno, string $message, ?string $file = null, ?int $line = null, ?array $context = null)
    {
        $this->phpErrors[] = ['errno'   => $errno,
                              'message' => $message,
                              'file'    => $file,
                              'line'    => $line,
                              'context' => $context];

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