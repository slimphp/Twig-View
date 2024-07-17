<?php

/**
 * Slim Framework (http://slimframework.com)
 *
 * @license   https://github.com/slimphp/Twig-View/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\UriInterface;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\RouteInterface;
use Slim\Routing\RouteCollector;
use Slim\Views\TwigRuntimeExtension;

class TwigRuntimeExtensionTest extends TestCase
{
    /**
     * Create a route collector using the given base path.
     *
     * Note that this method would create mocks for the ResponseFactoryInterface
     * and the CallableResolverInterface injected into the constructor of the
     * RouteCollector.
     *
     * @param string $basePath
     *
     * @return RouteCollector
     */
    protected function createRouteCollector(string $basePath): RouteCollector
    {
        $routeCollector = new RouteCollector(
            $this->createMock(ResponseFactoryInterface::class),
            $this->createMock(CallableResolverInterface::class)
        );

        if ($basePath) {
            $routeCollector->setBasePath($basePath);
        }

        return $routeCollector;
    }

    /**
     * Map a route to the given route collector.
     *
     * @param RouteCollector $routeCollector
     * @param array $methods
     * @param string $pattern
     * @param string $routeName
     *
     * @return RouteInterface
     */
    protected function mapRouteCollectorRoute(
        RouteCollector $routeCollector,
        array $methods,
        string $pattern,
        string $routeName
    ): RouteInterface {
        $route = $routeCollector->map($methods, $pattern, 'handler');
        $route->setName($routeName);

        return $route;
    }

    public static function isCurrentUrlProvider(): array
    {
        return [
            ['/hello/{name}', ['name' => 'world'], '/hello/world', '/base-path', true],
            ['/hello/{name}', ['name' => 'world'], '/hello/world', '', true],
            ['/hello/{name}', ['name' => 'world'], '/hello/john', '/base-path', false],
            ['/hello/{name}', ['name' => 'world'], '/hello/john', '', false],
        ];
    }

    /**
     * @dataProvider isCurrentUrlProvider
     *
     * @param string $pattern
     * @param array $data
     * @param string $path
     * @param string|null $basePath
     * @param bool $expected
     */
    public function testIsCurrentUrl(string $pattern, array $data, string $path, ?string $basePath, bool $expected)
    {
        $routeCollector = $this->createRouteCollector($basePath);
        $routeParser = $routeCollector->getRouteParser();

        $routeName = 'route';
        $this->mapRouteCollectorRoute($routeCollector, ['GET'], $pattern, $routeName);

        $uriProphecy = $this->prophesize(UriInterface::class);

        $uriProphecy
            ->getPath()
            ->willReturn($path)
            ->shouldBeCalledOnce();

        /** @var UriInterface $uri */
        $uri = $uriProphecy->reveal();

        $extension = new TwigRuntimeExtension($routeParser, $uri, $basePath);
        $result = $extension->isCurrentUrl($routeName, $data);

        $this->assertEquals($expected, $result);
    }

    public static function currentUrlProvider(): array
    {
        return [
            ['/hello/{name}', 'http://example.com/hello/world?a=b', '', true],
            ['/hello/{name}', 'http://example.com/hello/world', '', false],
            ['/base-path/hello/{name}', 'http://example.com/base-path/hello/world?a=b', '/base-path', true],
            ['/base-path/hello/{name}', 'http://example.com/base-path/hello/world', '/base-path', false],
        ];
    }

    /**
     * @dataProvider currentUrlProvider
     *
     * @param string $pattern
     * @param string $url
     * @param string $basePath
     * @param bool $withQueryString
     */
    public function testCurrentUrl(string $pattern, string $url, string $basePath, bool $withQueryString)
    {
        $routeCollector = $this->createRouteCollector($basePath);
        $routeParser = $routeCollector->getRouteParser();

        $routeName = 'route';
        $this->mapRouteCollectorRoute($routeCollector, ['GET'], $pattern, $routeName);

        $uriProphecy = $this->prophesize(UriInterface::class);

        $path = parse_url($url, PHP_URL_PATH);
        $query = parse_url($url, PHP_URL_QUERY) ?? '';

        $uriProphecy
            ->getPath()
            ->willReturn($path)
            ->shouldBeCalledOnce();

        $uriProphecy
            ->getQuery()
            ->willReturn($query)
            ->shouldBeCalledOnce();

        $expected = $basePath . $path;
        if ($withQueryString) {
            $expected .= '?' . $query;
        }

        /** @var UriInterface $uri */
        $uri = $uriProphecy->reveal();

        $extension = new TwigRuntimeExtension($routeParser, $uri, $basePath);
        $result = $extension->getCurrentUrl($withQueryString);

        $this->assertEquals($expected, $result);
    }

    public static function urlForProvider(): array
    {
        return [
            ['/hello/{name}', ['name' => 'world'], [], '', '/hello/world'],
            ['/hello/{name}', ['name' => 'world'], [], '/base-path', '/base-path/hello/world'],
            ['/hello/{name}', ['name' => 'world'], ['foo' => 'bar'], '', '/hello/world?foo=bar'],
            ['/hello/{name}', ['name' => 'world'], ['foo' => 'bar'], '/base-path', '/base-path/hello/world?foo=bar'],
        ];
    }

    /**
     * @dataProvider urlForProvider
     *
     * @param string $pattern
     * @param array $routeData
     * @param array $queryParams
     * @param string $basePath
     * @param string $expectedUrl
     */
    public function testUrlFor(
        string $pattern,
        array $routeData,
        array $queryParams,
        string $basePath,
        string $expectedUrl
    ) {
        $routeName = 'route';

        $routeCollector = $this->createRouteCollector($basePath);
        $this->mapRouteCollectorRoute($routeCollector, ['GET'], $pattern, $routeName);

        $uriProphecy = $this->prophesize(UriInterface::class);

        /** @var UriInterface $uri */
        $uri = $uriProphecy->reveal();

        $extension = new TwigRuntimeExtension($routeCollector->getRouteParser(), $uri, $routeCollector->getBasePath());
        $this->assertEquals($expectedUrl, $extension->urlFor($routeName, $routeData, $queryParams));
    }

    public static function fullUrlForProvider(): array
    {
        return [
            ['/hello/{name}', ['name' => 'world'], [], '', 'http://localhost/hello/world'],
            ['/hello/{name}', ['name' => 'world'], [], '/base-path', 'http://localhost/base-path/hello/world'],
            ['/hello/{name}', ['name' => 'world'], ['foo' => 'bar'], '', 'http://localhost/hello/world?foo=bar'],
            [
                '/hello/{name}',
                ['name' => 'world'],
                ['foo' => 'bar'],
                '/base-path',
                'http://localhost/base-path/hello/world?foo=bar',
            ],
        ];
    }

    /**
     * @dataProvider fullUrlForProvider
     *
     * @param string $pattern
     * @param array $routeData
     * @param array $queryParams
     * @param string $basePath
     * @param string $expectedFullUrl
     */
    public function testFullUrlFor(
        string $pattern,
        array $routeData,
        array $queryParams,
        string $basePath,
        string $expectedFullUrl
    ) {
        $routeName = 'route';

        $routeCollector = $this->createRouteCollector($basePath);
        $this->mapRouteCollectorRoute($routeCollector, ['GET'], $pattern, $routeName);

        $uriProphecy = $this->prophesize(UriInterface::class);

        $uriProphecy->getScheme()
            ->willReturn('http')
            ->shouldBeCalledOnce();

        $uriProphecy->getAuthority()
            ->willReturn('localhost')
            ->shouldBeCalledOnce();

        /** @var UriInterface $uri */
        $uri = $uriProphecy->reveal();

        $extension = new TwigRuntimeExtension($routeCollector->getRouteParser(), $uri, $routeCollector->getBasePath());
        $this->assertEquals($expectedFullUrl, $extension->fullUrlFor($routeName, $routeData, $queryParams));
    }

    public function testSetUri()
    {
        $basePath = '';

        $routeCollector = $this->createRouteCollector($basePath);
        $uri = $this->createMock(UriInterface::class);

        $extension = new TwigRuntimeExtension(
            $routeCollector->getRouteParser(),
            $uri,
            $routeCollector->getBasePath()
        );

        $uri2 = $this->createMock(UriInterface::class);
        $extension->setUri($uri2);
        $this->assertEquals($uri2, $extension->getUri());
    }

    public function testSetBasePath()
    {
        $basePath = '';

        $routeCollector = $this->createRouteCollector($basePath);
        $uri = $this->createMock(UriInterface::class);

        $extension = new TwigRuntimeExtension(
            $routeCollector->getRouteParser(),
            $uri,
            $routeCollector->getBasePath()
        );

        $basePath = '/base-path';
        $extension->setBasePath($basePath);
        $this->assertEquals($basePath, $extension->getBasePath());
    }
}
