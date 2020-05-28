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

namespace Berlioz\Core\App;

use Berlioz\Core\Asset\Assets;
use Berlioz\Core\Core;
use Berlioz\Core\CoreAwareInterface;
use Berlioz\Core\CoreAwareTrait;
use Berlioz\Core\Exception\BerliozException;
use Berlioz\ServiceContainer\Exception\ContainerException;
use Berlioz\ServiceContainer\Service;

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
     * @param Core|null $core
     *
     * @throws BerliozException
     * @throws ContainerException
     */
    public function __construct(?Core $core = null)
    {
        if (null === $core) {
            $core = new Core();
        }

        $this->setCore($core);

        // Add me to services
        $this->getCore()->getServiceContainer()->add(new Service($this, 'app'));
    }

    /**
     * Get service.
     *
     * @param string $id
     *
     * @return mixed
     * @throws BerliozException
     */
    public function getService(string $id)
    {
        return $this->getCore()->getServiceContainer()->get($id);
    }

    /**
     * Get assets.
     *
     * @return Assets
     * @throws BerliozException
     */
    public function getAssets(): Assets
    {
        return $this->getService(Assets::class);
    }
}