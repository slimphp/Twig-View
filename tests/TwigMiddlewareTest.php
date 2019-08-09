<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @license   https://github.com/slimphp/Twig-View/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests;

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
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
use Slim\Views\TwigExtension;
use Slim\Views\TwigMiddleware;
use Slim\Views\TwigRuntimeExtension;
use Slim\Views\TwigRuntimeLoader;

class TwigMiddlewareTest extends TestCase
{
    /**
     * Create a twig prophecy given a uri prophecy and a base path.
     *
     * @param ObjectProphecy $uriProphecy
     * @param string         $basePath
     *
     * @return ObjectProphecy
     */
    private function createTwigProphecy(ObjectProphecy $uriProphecy, string $basePath): ObjectProphecy
    {
        $self = $this;

        $twigProphecy = $this->prophesize(Twig::class);

        /** @noinspection PhpUndefinedMethodInspection */
        $twigProphecy
            ->addExtension(Argument::type('object'))
            ->will(function ($args) use ($self) {
                /** @var TwigExtension $extension */
                $extension = $args[0];

                $self->assertEquals('slim', $extension->getName());
            })
            ->shouldBeCalledOnce();

        /** @noinspection PhpUndefinedMethodInspection */
        $twigProphecy->
        addRuntimeLoader(Argument::type('object'))
            ->will(function ($args) use ($self, $uriProphecy, $basePath) {
                /** @var TwigRuntimeLoader $runtimeLoader */
                $runtimeLoader = $args[0];
                $runtimeExtension = $runtimeLoader->load(TwigRuntimeExtension::class);

                $self->assertInstanceOf(TwigRuntimeExtension::class, $runtimeExtension);

                /** @var TwigRuntimeExtension $runtimeExtension */
                $self->assertSame($uriProphecy->reveal(), $runtimeExtension->getUri());
                $self->assertSame($basePath, $runtimeExtension->getBasePath());
            })
            ->shouldBeCalledOnce();

        return $twigProphecy;
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
     * @expectedExceptionMessage The specified container key does not exist: view
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

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Twig instance could not be resolved via container key: view
     */
    public function testCreateWithoutTwig()
    {
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('has')
            ->with($this->equalTo('view'))
            ->willReturn(true);
        $container
            ->method('get')
            ->with($this->equalTo('view'))
            ->willReturn(null);

        $app = $this->createMock(App::class);
        $app->method('getContainer')->willReturn($container);

        TwigMiddleware::create($app);
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
