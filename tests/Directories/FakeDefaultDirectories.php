<?php

namespace Berlioz\Core\Tests\Directories;

use Berlioz\Core\Directories\DefaultDirectories;

class FakeDefaultDirectories extends DefaultDirectories
{
    protected function getLibraryDirectory(): string
    {
        return realpath(__DIR__ . '/../_envTest/vendor/berlioz/core');
    }
}