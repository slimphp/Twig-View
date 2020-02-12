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

    public function testCreateFromContainer()
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

        $middleware = TwigMiddleware::createFromContainer($app, $key);

        $this->assertInaccessiblePropertySame($twig, $middleware, 'twig');
        $this->assertInaccessiblePropertySame($routeParser, $middleware, 'routeParser');
        $this->assertInaccessiblePropertySame($basePath, $middleware, 'basePath');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The app does not have a container.
     */
    public function testCreateFromContainerWithoutContainer()
    {
        $app = $this->createMock(App::class);
        TwigMiddleware::createFromContainer($app);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The specified container key does not exist: view
     */
    public function testCreateFromContainerWithoutContainerKey()
    {
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('has')
            ->with($this->equalTo('view'))
            ->willReturn(false);

        $app = $this->createMock(App::class);
        $app->method('getContainer')->willReturn($container);

        TwigMiddleware::createFromContainer($app);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Twig instance could not be resolved via container key: view
     */
    public function testCreateFromContainerWithoutTwig()
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

        TwigMiddleware::createFromContainer($app);
    }

    public function testCreate()
    {
        $routeParser = $this->createMock(RouteParserInterface::class);
        $routeCollector = $this->createMock(RouteCollectorInterface::class);
        $routeCollector->method('getRouteParser')->willReturn($routeParser);

        $basePath = '/base-path';
        $app = $this->createMock(App::class);
        $app->method('getRouteCollector')->willReturn($routeCollector);
        $app->method('getBasePath')->willReturn($basePath);

        $twig = $this->createMock(Twig::class);
        $attributeName = 'twig';

        $middleware = TwigMiddleware::create($app, $twig, $attributeName);

        $this->assertInaccessiblePropertySame($twig, $middleware, 'twig');
        $this->assertInaccessiblePropertySame($routeParser, $middleware, 'routeParser');
        $this->assertInaccessiblePropertySame($basePath, $middleware, 'basePath');
        $this->assertInaccessiblePropertySame($attributeName, $middleware, 'attributeName');
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

    public function testProcessWithRequestAttribute()
    {
        $routeParser = $this->createMock(RouteParserInterface::class);
        $uriProphecy = $this->prophesize(UriInterface::class);

        /** @var Twig $twig */
        $twig = $this->createTwigProphecy($uriProphecy, '')->reveal();

        $twigMiddleware = new TwigMiddleware($twig, $routeParser, '', 'view');

        $responseProphecy = $this->prophesize(ResponseInterface::class);

        // Prophesize the server request that would be returned in the `withAttribute` method.
        $requestProphecy2 = $this->prophesize(ServerRequestInterface::class);

        // Prophesize the server request.
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        /** @noinspection PhpUndefinedMethodInspection */
        $requestProphecy->withAttribute('view', Argument::type(Twig::class))
            ->shouldBeCalledOnce()
            ->will(function ($args) use ($requestProphecy2): ServerRequestInterface {
                /** @noinspection PhpUndefinedMethodInspection */
                $requestProphecy2->getAttribute('view')
                    ->shouldBeCalledOnce()
                    ->willReturn($args[1]);

                /** @noinspection PhpIncompatibleReturnTypeInspection */
                return $requestProphecy2->reveal();
            });

        /** @noinspection PhpUndefinedMethodInspection */
        $requestProphecy
            ->getUri()
            ->willReturn($uriProphecy->reveal())
            ->shouldBeCalledOnce();

        // Prophesize the request handler.
        $requestHandlerProphecy = $this->prophesize(RequestHandlerInterface::class);
        $that = $this;
        /** @noinspection PhpUndefinedMethodInspection */
        $requestHandlerProphecy
            ->handle($requestProphecy2->reveal())
            ->shouldBeCalledOnce()
            ->will(function ($args) use ($that, $twig, $responseProphecy): ResponseInterface {
                /** @var ServerRequestInterface $serverRequest */
                $serverRequest = $args[0];
                $that->assertSame($twig, $serverRequest->getAttribute('view'));

                /** @noinspection PhpIncompatibleReturnTypeInspection */
                return $responseProphecy->reveal();
            });

        /** @noinspection PhpParamsInspection */
        $twigMiddleware->process($requestProphecy->reveal(), $requestHandlerProphecy->reveal());
    }
}
