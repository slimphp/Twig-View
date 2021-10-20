<?php

/**
 * Slim Framework (http://slimframework.com)
 *
 * @license   https://github.com/slimphp/Twig-View/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests;

use Slim\Views\TwigExtension;

use function count;

class TwigExtensionTest extends TestCase
{
    public function testGetName()
    {
        $extension = new TwigExtension();
        $this->assertEquals('slim', $extension->getName());
    }

    public function testGetFunctions()
    {
        $expectedFunctionNames = [
            'url_for',
            'full_url_for',
            'is_current_url',
            'current_url',
            'get_uri',
            'base_path',
        ];

        $extension = new TwigExtension();
        $functions = $extension->getFunctions();
        for ($i = count($functions) - 1; $i >= 0; $i--) {
            $this->assertEquals($expectedFunctionNames[$i], $functions[$i]->getName());
        }
    }
}
