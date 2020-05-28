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

namespace Berlioz\Core\Tests\Debug;

use Berlioz\Core\Debug\PhpError;
use PHPUnit\Framework\TestCase;

class PhpErrorTest extends TestCase
{
    public function test()
    {
        $phpError = new PhpError();

        $this->assertEquals('PHP Errors', $phpError->getSectionName());
        $this->assertEquals('php-errors', $phpError->getSectionId());
        $this->assertCount(0, $phpError);

        $error1 = ['errno' => 1, 'message' => 'Foo', 'file' => 'bar.php', 'line' => 123];
        $error2 = ['errno' => 2, 'message' => 'Bar', 'file' => 'foo.php', 'line' => 321];

        $phpError->phpErrorHandler(...array_values($error1));
        $phpError->phpErrorHandler(...array_values($error2));

        $this->assertCount(2, $phpError);
        $this->assertEquals([$error1, $error2], $phpError->getPhpErrors());
        $this->assertEquals(var_export($phpError->getPhpErrors(), true), (string)$phpError);
        $this->assertEquals(unserialize(serialize($phpError)), $phpError);

        $phpError->handle();
        @trigger_error('Qux', E_USER_NOTICE);
        restore_error_handler();

        $this->assertCount(3, $phpError);
        $this->assertEquals(
            [
                $error1,
                $error2,
                ['errno' => E_USER_NOTICE, 'message' => 'Qux', 'file' => __FILE__, 'line' => 30]
            ],
            $phpError->getPhpErrors()
        );
    }
}
