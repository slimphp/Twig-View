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
use Slim\Routing\RouteCollector;
use Slim\Views\TwigExtension;

class TwigExtensionTest extends TestCase
{
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
     */
    public function testIsCurrentUrl(string $pattern, array $data, string $path, ?string $basePath, bool $expected)
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);

        $routeCollector = new RouteCollector($responseFactoryProphecy->reveal(), $callableResolverProphecy->reveal());
        $routeParser = $routeCollector->getRouteParser();

        if ($basePath) {
            $routeCollector->setBasePath($basePath);
        }

        $routeName = 'route';
        $route = $routeCollector->map(['GET'], $pattern, 'handler');
        $route->setName($routeName);

        $uriProphecy = $this->prophesize(UriInterface::class);

        $uriProphecy
            ->getPath()
            ->willReturn($path)
            ->shouldBeCalledOnce();

        $extension = new TwigExtension($routeParser, $uriProphecy->reveal(), $basePath);
        $result = $extension->isCurrentUrl($routeName, $data);

        $this->assertEquals($expected, $result);
    }

    public function currentUrlProvider()
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
     */
    public function testCurrentUrl(string $pattern, string $url, string $basePath, bool $withQueryString)
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);

        $routeCollector = new RouteCollector($responseFactoryProphecy->reveal(), $callableResolverProphecy->reveal());
        $routeParser = $routeCollector->getRouteParser();

        $routeName = 'route';
        $route = $routeCollector->map(['GET'], $pattern, 'handler');
        $route->setName($routeName);

        $uriProphecy = $this->prophesize(UriInterface::class);

        $path = parse_url($url, PHP_URL_PATH);
        $query = parse_url($url, PHP_URL_QUERY);

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

        $extension = new TwigExtension($routeParser, $uriProphecy->reveal(), $basePath);
        $result = $extension->getCurrentUrl($withQueryString);

        $this->assertEquals($expected, $result);
    }
}
