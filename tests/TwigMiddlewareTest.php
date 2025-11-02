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
use Slim\Views\TwigMiddleware;
use Slim\Views\TwigRuntimeExtension;
use Slim\Views\TwigRuntimeLoader;
use Twig\RuntimeLoader\RuntimeLoaderInterface;

class TwigMiddlewareTest extends TestCase
{
    /**
     * Create a twig mock given a uri mock and a base path.
     *
     * @param UriInterface $uri
     * @param string $basePath
     *
     * @return Twig
     */
    private function createTwigMock(UriInterface $uri, string $basePath)
    {
        $self = $this;

        $twigMock = $this->createMock(Twig::class);

        $twigMock->expects($this->once())
            ->method('addRuntimeLoader')
            ->with($this->isInstanceOf(RuntimeLoaderInterface::class))
            ->willReturnCallback(function ($runtimeLoader) use ($self, $uri, $basePath) {
                /** @var TwigRuntimeLoader $runtimeLoader */
                $runtimeExtension = $runtimeLoader->load(TwigRuntimeExtension::class);

                $self->assertInstanceOf(TwigRuntimeExtension::class, $runtimeExtension);

                /** @var TwigRuntimeExtension $runtimeExtension */
                $self->assertSame($uri, $runtimeExtension->getUri());
                $self->assertSame($basePath, $runtimeExtension->getBasePath());
            });

        return $twigMock;
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

    public function testCreateFromContainerWithoutContainer()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The app does not have a container.');

        $app = $this->createMock(App::class);
        TwigMiddleware::createFromContainer($app);
    }

    public function testCreateFromContainerWithoutContainerKey()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The specified container key does not exist: view');

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('has')
            ->with($this->equalTo('view'))
            ->willReturn(false);

        $app = $this->createMock(App::class);
        $app->method('getContainer')->willReturn($container);

        TwigMiddleware::createFromContainer($app);
    }

    public function testCreateFromContainerWithoutTwig()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Twig instance could not be resolved via container key: view');

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
        $uri = $this->createMock(UriInterface::class);
        $twig = $this->createTwigMock($uri, $basePath);
        $routeParser = $this->createMock(RouteParserInterface::class);

        $twigMiddleware = new TwigMiddleware(
            $twig,
            $routeParser,
            $basePath
        );

        $response = $this->createMock(ResponseInterface::class);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())
            ->method('getUri')
            ->willReturn($uri);

        $requestHandler = $this->createMock(RequestHandlerInterface::class);
        $requestHandler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($response);

        $twigMiddleware->process($request, $requestHandler);
    }

    public function testProcessWithRequestAttribute()
    {
        $routeParser = $this->createMock(RouteParserInterface::class);
        $uri = $this->createMock(UriInterface::class);

        /** @var Twig $twig */
        $twig = $this->createTwigMock($uri, '');

        $twigMiddleware = new TwigMiddleware($twig, $routeParser, '', 'view');

        $response = $this->createMock(ResponseInterface::class);

        // Create the server request that would be returned in the `withAttribute` method.
        $request2 = $this->createMock(ServerRequestInterface::class);

        // Create the server request.
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())
            ->method('withAttribute')
            ->with('view', $this->isInstanceOf(Twig::class))
            ->willReturnCallback(function ($name, $value) use ($request2) {
                $request2->expects($this->once())
                    ->method('getAttribute')
                    ->with('view')
                    ->willReturn($value);

                return $request2;
            });

        $request->expects($this->once())
            ->method('getUri')
            ->willReturn($uri);

        // Create the request handler.
        $requestHandler = $this->createMock(RequestHandlerInterface::class);
        $that = $this;
        $requestHandler->expects($this->once())
            ->method('handle')
            ->with($request2)
            ->willReturnCallback(function ($serverRequest) use ($that, $twig, $response): ResponseInterface {
                /** @var ServerRequestInterface $serverRequest */
                $that->assertSame($twig, $serverRequest->getAttribute('view'));

                return $response;
            });

        $twigMiddleware->process($request, $requestHandler);
    }
}
