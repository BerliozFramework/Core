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

use Berlioz\Core\App;

interface TemplateEngine extends App\AppAwareInterface, PackageInterface
{
    /**
     * Add global variable.
     *
     * @param string $name  Variable name
     * @param mixed  $value Variable value
     *
     * @return static
     */
    public function addGlobal(string $name, $value): TemplateEngine;

    /**
     * Register a new path for template engine.
     *
     * @param string      $path      Path
     * @param string|null $namespace Namespace
     *
     * @return static
     */
    public function registerPath(string $path, string $namespace = null): TemplateEngine;

    /**
     * Render a template.
     *
     * @param string $name      Template filename
     * @param array  $variables Variables
     *
     * @return string
     */
    public function render(string $name, array $variables = []): string;

    /**
     * Has block in template ?
     *
     * @param string $tplName   Template filename
     * @param string $blockName Block name
     *
     * @return bool
     */
    public function hasBlock(string $tplName, string $blockName): bool;

    /**
     * Render a block in template.
     *
     * @param string $tplName   Template filename
     * @param string $blockName Block name
     * @param array  $variables Variables
     *
     * @return string
     */
    public function renderBlock(string $tplName, string $blockName, array $variables = []): string;
}