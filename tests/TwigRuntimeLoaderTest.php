<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @license   https://github.com/slimphp/Twig-View/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests;

use Psr\Http\Message\UriInterface;
use ReflectionProperty;
use Slim\Interfaces\RouteParserInterface;
use Slim\Views\TwigRuntimeExtension;
use Slim\Views\TwigRuntimeLoader;

class TwigRuntimeLoaderTest extends TestCase
{
    public function testConstructor()
    {
        $routeParser = $this->createMock(RouteParserInterface::class);
        $uri = $this->createMock(UriInterface::class);
        $basePath = '';

        // Create the twig runtime loader.
        $twigRuntimeLoader = new TwigRuntimeLoader($routeParser, $uri, $basePath);

        // Make `TwigRuntimeLoader->routeParser` accessible.
        $routeParserProperty = new ReflectionProperty(TwigRuntimeLoader::class, 'routeParser');
        $routeParserProperty->setAccessible(true);

        // Make `TwigRuntimeLoader->uri` accessible.
        $uriProperty = new ReflectionProperty(TwigRuntimeLoader::class, 'uri');
        $uriProperty->setAccessible(true);

        // Make `TwigRuntimeLoader->basePath` accessible.
        $basePathProperty = new ReflectionProperty(TwigRuntimeLoader::class, 'basePath');
        $basePathProperty->setAccessible(true);

        $this->assertSame($routeParser, $routeParserProperty->getValue($twigRuntimeLoader));
        $this->assertSame($uri, $uriProperty->getValue($twigRuntimeLoader));
        $this->assertSame($basePath, $basePathProperty->getValue($twigRuntimeLoader));
    }

    public function testLoad()
    {
        $routeParser = $this->createMock(RouteParserInterface::class);
        $uri = $this->createMock(UriInterface::class);
        $basePath = '';

        // Create the twig runtime loader.
        $twigRuntimeLoader = new TwigRuntimeLoader($routeParser, $uri, $basePath);

        $runtimeExtension = $twigRuntimeLoader->load(TwigRuntimeExtension::class);
        $this->assertInstanceOf(TwigRuntimeExtension::class, $runtimeExtension);
    }

    public function testLoadUnsupportedRuntimeExtension()
    {
        $routeParser = $this->createMock(RouteParserInterface::class);
        $uri = $this->createMock(UriInterface::class);
        $basePath = '';

        // Create the twig runtime loader.
        $twigRuntimeLoader = new TwigRuntimeLoader($routeParser, $uri, $basePath);

        $runtimeExtension = $twigRuntimeLoader->load('UnsupportedRuntimeExtension');
        $this->assertNull($runtimeExtension);
    }
}
