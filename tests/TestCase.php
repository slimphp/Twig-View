<?php

/**
 * Slim Framework (http://slimframework.com)
 *
 * @license   https://github.com/slimphp/Twig-View/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests;

use PHPUnit\Framework\TestCase as PhpUnitTestCase;
use ReflectionProperty;

use function get_class;

abstract class TestCase extends PhpUnitTestCase
{
    protected function assertInaccessiblePropertySame($expected, $obj, string $name)
    {
        $prop = new ReflectionProperty(get_class($obj), $name);
        if (PHP_VERSION_ID < 80100) {
            $prop->setAccessible(true);
        }
        $this->assertSame($expected, $prop->getValue($obj));
    }
}
