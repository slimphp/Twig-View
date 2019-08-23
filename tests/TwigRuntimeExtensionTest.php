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
     * @param array          $methods
     * @param string         $pattern
     * @param string         $routeName
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

    public function relativePathProvider()
    {
        return [
            ['/a',                  '/a',                   'a'],
            ['/a',                  '/b',                   'a'],
            ['/one/a',              '/one/b',               'a'],
            ['/one/two/a',          '/one/two/b',           'a'],
            ['/one/two/three/a',    '/one/two/three/b',     'a'],

            ['/a/b',                '/c',                   'a/b'],
            ['/a/b',                '/a',                   'a/b'],

            ['/',                   '/a/',                  '../'],
            ['/',                   '/a/b',                 '../'],
            ['/',                   '/a/b/',                '../../'],
            ['/',                   '/a/b/c',               '../../'],
            ['/',                   '/a/b/c/',              '../../../'],
            ['/',                   '/a/b/c/d',             '../../../'],

            ['/a',                  '/b/c',                 '../a'],
            ['/a',                  '/b/c/',                '../../a'],
            ['/a',                  '/b/c/d',               '../../a'],
            ['/a',                  '/b/c/d/',              '../../../a'],
            ['/a',                  '/b/c/d/e',             '../../../a'],

            ['/a',                  '/a/b',                 '../a'],
            ['/a',                  '/a/b/',                '../../a'],
            ['/a',                  '/a/b/c',               '../../a'],
            ['/a',                  '/a/b/c/',              '../../../a'],
            ['/a',                  '/a/b/c/d',             '../../../a'],

            ['/',                   '/',                    './'],
            ['/',                   '/a',                   './'],
        ];
    }

    /**
     * @dataProvider relativePathProvider
     *
     * @param string      $to
     * @param string      $from
     * @param string      $expected
     */
    public function testRelativePath(string $to, string $from, string $expected)
    {
        $this->assertEquals($expected, TwigRuntimeExtension::relativePath($to, $from));
    }

    public function isCurrentUrlProvider()
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
     * @param string      $pattern
     * @param array       $data
     * @param string      $path
     * @param string|null $basePath
     * @param bool        $expected
     */
    public function testIsCurrentUrl(string $pattern, array $data, string $path, ?string $basePath, bool $expected)
    {
        $routeCollector = $this->createRouteCollector($basePath);
        $routeParser = $routeCollector->getRouteParser();

        $routeName = 'route';
        $this->mapRouteCollectorRoute($routeCollector, ['GET'], $pattern, $routeName);

        $uriProphecy = $this->prophesize(UriInterface::class);

        /** @noinspection PhpUndefinedMethodInspection */
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

    public function currentUrlProvider()
    {
        return [
            ['', '/hello/world', '', false, '/hello/world'],
            ['', '/hello/world', '', true, '/hello/world'],
            ['', '/hello/world', 'a=b', false, '/hello/world'],
            ['', '/hello/world', 'a=b', true, '/hello/world?a=b'],

            ['/base-path', '/hello/world', '', false, '/base-path/hello/world'],
            ['/base-path', '/hello/world', '', true, '/base-path/hello/world'],
            ['/base-path', '/hello/world', 'a=b', false, '/base-path/hello/world'],
            ['/base-path', '/hello/world', 'a=b', true, '/base-path/hello/world?a=b'],
        ];
    }

    /**
     * @dataProvider currentUrlProvider
     *
     * @param string $basePath
     * @param string $path
     * @param string $query
     * @param bool   $withQueryString
     * @param string $expected
     */
    public function testCurrentUrl(string $basePath, string $path, string $query, bool $withQueryString, string $expected)
    {
        $routeCollector = $this->createRouteCollector($basePath);
        $routeParser = $routeCollector->getRouteParser();

        $uriProphecy = $this->prophesize(UriInterface::class);

        /** @noinspection PhpUndefinedMethodInspection */
        $uriProphecy
            ->getPath()
            ->willReturn($path)
            ->shouldBeCalledOnce();

        /** @noinspection PhpUndefinedMethodInspection */
        $uriProphecy
            ->getQuery()
            ->willReturn($query)
            ->shouldBeCalledOnce();

        /** @var UriInterface $uri */
        $uri = $uriProphecy->reveal();

        $extension = new TwigRuntimeExtension($routeParser, $uri, $basePath);
        $result = $extension->getCurrentUrl($withQueryString);

        $this->assertEquals($expected, $result);
    }

    public function urlForProvider()
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
     * @param array  $routeData
     * @param array  $queryParams
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

    public function relativeUrlForProvider()
    {
        return [
             ['',           '/user/1/',  '',    '/user/{id}/',     ['id' => 1], [],           './'],
             ['/base-path', '/user/1/',  'a=b', '/user/{id}/edit', ['id' => 1], [],           'edit'],
             ['',           '/user/1/',  'a=b', '/user/',          [],          ['s' => 'n'], '../?s=n'],
             ['/base-path', '/user/add', '',    '/user/',          [],          ['p' => 2],   './?p=2'],
        ];
    }

    /**
     * @dataProvider relativeUrlForProvider
     *
     * @param string $basePath
     * @param string $path
     * @param string $query
     * @param string $pattern
     * @param array  $routeData
     * @param array  $queryParams
     * @param string $expectedUrl
     */
    public function testRelativeUrlFor(
        string $basePath,
        string $path,
        string $query,
        string $pattern,
        array $routeData,
        array $queryParams,
        string $expectedUrl
    ) {
        $routeName = 'route';

        $routeCollector = $this->createRouteCollector($basePath);
        $this->mapRouteCollectorRoute($routeCollector, ['GET'], $pattern, $routeName);

        $uriProphecy = $this->prophesize(UriInterface::class);

        /** @noinspection PhpUndefinedMethodInspection */
        $uriProphecy
            ->getPath()
            ->willReturn($path)
            ->shouldBeCalledOnce();

        /** @noinspection PhpUndefinedMethodInspection */
        $uriProphecy
            ->getQuery()
            ->willReturn($query)
            ->shouldBeCalledOnce();

        /** @var UriInterface $uri */
        $uri = $uriProphecy->reveal();

        $extension = new TwigRuntimeExtension($routeCollector->getRouteParser(), $uri, $routeCollector->getBasePath());
        $this->assertEquals($expectedUrl, $extension->relativeUrlFor($routeName, $routeData, $queryParams));
    }

    public function fullUrlForProvider()
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
     * @param array  $routeData
     * @param array  $queryParams
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

        /** @noinspection PhpUndefinedMethodInspection */
        $uriProphecy->getScheme()
            ->willReturn('http')
            ->shouldBeCalledOnce();

        /** @noinspection PhpUndefinedMethodInspection */
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
