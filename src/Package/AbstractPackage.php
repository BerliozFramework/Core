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

namespace Berlioz\Core\Package;

use Berlioz\Core\App\AbstractApp;
use Berlioz\Core\App\AppAwareInterface;
use Berlioz\Core\App\AppAwareTrait;

abstract class AbstractPackage implements PackageInterface, AppAwareInterface
{
    use AppAwareTrait;

    /**
     * AbstractPackage constructor.
     *
     * @param \Berlioz\Core\App\AbstractApp $app
     */
    public function __construct(AbstractApp $app)
    {
        $this->setApp($app);
    }

    /**
     * @inheritdoc
     */
    public function getDefaultConfigFilename(): ?string
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
    }

    /**
     * Register template path.
     *
     * @param string      $path
     * @param string|null $namespace
     *
     * @return static
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function registerTemplatePath(string $path, string $namespace): AbstractPackage
    {
        if ($this->getApp()->getServiceContainer()->has(TemplateEngine::class)) {
            /** @var \Berlioz\Core\Package\TemplateEngine $templateEngine */
            $templateEngine = $this->getApp()->getServiceContainer()->get(TemplateEngine::class);
            $templateEngine->registerPath($path, $namespace);
        }

        return $this;
    }
}