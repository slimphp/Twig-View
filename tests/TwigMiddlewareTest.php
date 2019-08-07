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
use Slim\App;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteParserInterface;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

class TwigMiddlewareTest extends TestCase
{
    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The app does not have a container.
     */
    public function testCreateWithoutContainer()
    {
        $app = $this->createMock(App::class);
        TwigMiddleware::create($app);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage 'view' is not set on the container.
     */
    public function testCreateWithoutContainerKey()
    {
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('has')
            ->with($this->equalTo('view'))
            ->willReturn(false);

        $app = $this->createMock(App::class);
        $app->method('getContainer')->willReturn($container);

        TwigMiddleware::create($app);
    }

    public function testCreate()
    {
        $key = 'twig';
        $twig = $this->createMock(Twig::class);
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('has')
            ->with($this->equalTo($key))
            ->willReturn(true);
        $container
            ->method('get')
            ->with($this->equalTo($key))
            ->willReturn($twig);

        $routeParser = $this->createMock(RouteParserInterface::class);
        $routeCollector = $this->createMock(RouteCollectorInterface::class);
        $routeCollector->method('getRouteParser')->willReturn($routeParser);

        $basePath = '/base-path';
        $app = $this->createMock(App::class);
        $app->method('getContainer')->willReturn($container);
        $app->method('getRouteCollector')->willReturn($routeCollector);
        $app->method('getBasePath')->willReturn($basePath);

        $middleware = TwigMiddleware::create($app, $key);

        $this->assertInaccessiblePropertySame($twig, $middleware, 'twig');
        $this->assertInaccessiblePropertySame($routeParser, $middleware, 'routeParser');
        $this->assertInaccessiblePropertySame($basePath, $middleware, 'basePath');
    }

    public function testProcess()
    {
        $basePath = '/base-path';
        $uriProphecy = $this->prophesize(UriInterface::class);
        $twigProphecy = $this->createTwigProphecy($uriProphecy, $basePath);
        $routeParserProphecy = $this->prophesize(RouteParserInterface::class);

        /** @noinspection PhpParamsInspection */
        $twigMiddleware = new TwigMiddleware(
            $twigProphecy->reveal(),
            $routeParserProphecy->reveal(),
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
