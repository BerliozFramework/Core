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

namespace Berlioz\Core\Tests;

use Berlioz\Config\ConfigInterface;
use Berlioz\Config\JsonConfig;
use Berlioz\Core\Core;
use Berlioz\Core\Debug;
use Berlioz\Core\Tests\Debug\FakeSection;
use Berlioz\Core\Tests\Directories\FakeDefaultDirectories;
use PHPUnit\Framework\TestCase;

class DebugTest extends TestCase
{
    public function test()
    {
        $core = new Core(new FakeDefaultDirectories());
        $debug = new Debug($core);
        $debug->addSection(new FakeSection());

        $this->assertFalse($debug->isEnabled());

        $debug->setEnabled(true);
        $this->assertTrue($debug->isEnabled());

        $debug->setEnabled(false);
        $this->assertFalse($debug->isEnabled());

        $this->assertSame($debug->getConfig(), $core->getConfig());
        $this->assertIsString($debug->getUniqid());
        $this->assertInstanceOf(\DateTimeInterface::class, $debug->getDatetime());
        $this->assertInstanceOf(ConfigInterface::class, $debug->getConfig());
        $this->assertInstanceOf(Debug\TimeLine::class, $debug->getTimeLine());
        $this->assertInstanceOf(Debug\PhpError::class, $debug->getPhpError());
        $this->assertNull($debug->getExceptionThrown());
        $this->assertFalse($debug->hasExceptionThrown());

        $debug->setExceptionThrown(new \Error('foo'));

        $this->assertTrue($debug->hasExceptionThrown());
        $this->assertIsString($debug->getExceptionThrown());

        $this->assertEmpty($debug->getSystemInfo());
        $this->assertEmpty($debug->getPerformancesInfo());
        $this->assertEmpty($debug->getPhpInfo());
        $this->assertEmpty($debug->getProjectInfo());
        $this->assertCount(1, $debug->getSections());
        $this->assertEquals('bar', $debug->getSection('fake-section'));

        $this->assertEquals($debug, unserialize(gzinflate($debug->saveReport())));

        $this->assertNotEmpty($debug->getSystemInfo());
        $this->assertNotEmpty($debug->getPerformancesInfo());
        $this->assertNotEmpty($debug->getPhpInfo());
        $this->assertNotEmpty($debug->getProjectInfo());
        $this->assertCount(1, $debug->getSections());
        $this->assertEquals('bar', $debug->getSection('fake-section'));
    }

    public function testIsEnabledInConfig()
    {
        $core = new Core(new FakeDefaultDirectories());
        $debug = new Debug($core);

        $config = new JsonConfig('{"berlioz": {"debug": {"enable": true}}}');
        $this->assertTrue($debug->isEnabledInConfig($config));

        $config = new JsonConfig('{"berlioz": {"debug": {"enable": false}}}');
        $this->assertFalse($debug->isEnabledInConfig($config));

        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $config = new JsonConfig('{"berlioz": {"debug": {"enable": false, "ip": ["127.0.0.1"]}}}');
        $this->assertFalse($debug->isEnabledInConfig($config));

        $config = new JsonConfig('{"berlioz": {"debug": {"enable": true, "ip": ["127.0.0.1"]}}}');
        $this->assertTrue($debug->isEnabledInConfig($config));

        $_SERVER['REMOTE_ADDR'] = '127.0.0.2';

        $this->assertFalse($debug->isEnabledInConfig($config));
        
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1,127.0.0.2';

        $this->assertTrue($debug->isEnabledInConfig($config));

        $_SERVER['REMOTE_ADDR'] = '127.0.0.3, 127.0.0.4';

        $this->assertFalse($debug->isEnabledInConfig($config));

        $config = new JsonConfig(
            '{"berlioz": {"debug": {"enable": true, "ip": ["' . gethostbyaddr('127.0.0.1') . '"]}}}'
        );
        $this->assertTrue($debug->isEnabledInConfig($config));
    }
}
