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

namespace Berlioz\Core\App;

use Berlioz\Core\Core;
use Berlioz\Core\CoreAwareInterface;
use Berlioz\Core\CoreAwareTrait;

/**
 * Class AbstractApp.
 *
 * @package Berlioz\Core\App
 */
abstract class AbstractApp implements CoreAwareInterface
{
    use CoreAwareTrait;

    /**
     * AbstractApp constructor.
     *
     * @param \Berlioz\Core\Core|null $core
     *
     * @throws \Berlioz\Core\Exception\BerliozException
     * @throws \Berlioz\ServiceContainer\Exception\ContainerException
     */
    public function __construct(?Core $core = null)
    {
        if (is_null($core)) {
            $core = new Core();
        }

        $this->setCore($core);
    }

    /**
     * AbstractApp destructor.
     */
    public function __destruct()
    {
    }

    /**
     * Get service.
     *
     * @param string $id
     *
     * @return mixed
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function getService(string $id)
    {
        return $this->getCore()->getServiceContainer()->get($id);
    }
}