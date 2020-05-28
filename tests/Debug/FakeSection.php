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

use Berlioz\Core\Debug\AbstractSection;

class FakeSection extends AbstractSection
{
    private $foo = 'bar';

    public function __toString(): string
    {
        return $this->foo;
    }

    public function getSectionName(): string
    {
        return 'Fake section';
    }

    public function serialize()
    {
        return serialize(['foo' => $this->foo]);
    }

    public function unserialize($serialized)
    {
        $unserialized = unserialize($serialized);

        $this->foo = $unserialized['foo'];
    }
}