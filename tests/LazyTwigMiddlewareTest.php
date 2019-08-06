<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @license   https://github.com/slimphp/Twig-View/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Slim\App;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteParserInterface;
use Slim\Views\Twig;
use Slim\Views\LazyTwigMiddleware;

class LazyTwigMiddlewareTest extends TestCase
{
    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The app does not have a container.
     */
    public function testCreateWithoutContainer()
    {
        $app = $this->createMock(App::class);
        LazyTwigMiddleware::create($app);
    }

    public function testCreate()
    {
        $containerKey = 'twig';

        $container = $this->createMock(ContainerInterface::class);
        $routeParser = $this->createMock(RouteParserInterface::class);
        $basePath = '/base-path';

        $routeCollector = $this->createMock(RouteCollectorInterface::class);
        $routeCollector->method('getRouteParser')->willReturn($routeParser);

        $app = $this->createMock(App::class);
        $app->method('getContainer')->willReturn($container);
        $app->method('getRouteCollector')->willReturn($routeCollector);
        $app->method('getBasePath')->willReturn($basePath);

        $middleware = LazyTwigMiddleware::create($app, $containerKey);

        $this->assertInaccessiblePropertySame($container, $middleware, 'container');
        $this->assertInaccessiblePropertySame($containerKey, $middleware, 'containerKey');
        $this->assertInaccessiblePropertySame($routeParser, $middleware, 'routeParser');
        $this->assertInaccessiblePropertySame($basePath, $middleware, 'basePath');
    }

    public function testProcess()
    {
	$key = Twig::class;
        $basePath = '/base-path';
        $uriProphecy = $this->prophesize(UriInterface::class);
        $twigProphecy = $this->createTwigProphecy($uriProphecy, $basePath);

        $container = $this->prophesize(ContainerInterface::class);
        /** @noinspection PhpUndefinedMethodInspection */
        $container
            ->get($key)
            ->willReturn($twigProphecy->reveal())
            ->shouldBeCalledOnce();

        $routeParserProphecy = $this->prophesize(RouteParserInterface::class);

        /** @noinspection PhpParamsInspection */
        $twigMiddleware = new LazyTwigMiddleware(
            $routeParserProphecy->reveal(),
	    $container->reveal(),
	    $key,
	    $basePath
        );

        $responseProphecy = $this->prophesize(ResponseInterface::class);

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        /** @noinspection PhpUndefinedMethodInspection */
        $requestProphecy
            ->getUri()
            ->willReturn($uriProphecy->reveal())
            ->shouldBeCalledOnce();

        $requestHandlerProphecy = $this->prophesize(RequestHandlerInterface::class);
        /** @noinspection PhpUndefinedMethodInspection */
        $requestHandlerProphecy
            ->handle($requestProphecy->reveal())
            ->shouldBeCalledOnce()
            ->willReturn($responseProphecy->reveal());

        /** @noinspection PhpParamsInspection */
        $twigMiddleware->process($requestProphecy->reveal(), $requestHandlerProphecy->reveal());
    }
}
