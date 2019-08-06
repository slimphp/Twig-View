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
use ReflectionProperty;
use RuntimeException;
use Slim\App;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteParserInterface;
use Slim\Views\Twig;
use Slim\Views\TwigExtension;
use Slim\Views\LazyTwigMiddleware;
use Slim\Views\TwigRuntimeExtension;
use Slim\Views\TwigRuntimeLoader;

class LazyTwigMiddlewareTest extends TestCase
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

        $containerProperty = new ReflectionProperty(LazyTwigMiddleware::class, 'container');
        $containerProperty->setAccessible(true);
        $this->assertSame($container, $containerProperty->getValue($middleware));

        $containerKeyProperty = new ReflectionProperty(LazyTwigMiddleware::class, 'containerKey');
        $containerKeyProperty->setAccessible(true);
        $this->assertSame($containerKey, $containerKeyProperty->getValue($middleware));

        $routeParserProperty = new ReflectionProperty(LazyTwigMiddleware::class, 'routeParser');
        $routeParserProperty->setAccessible(true);
        $this->assertSame($routeParser, $routeParserProperty->getValue($middleware));

        $basePathProperty = new ReflectionProperty(LazyTwigMiddleware::class, 'basePath');
        $basePathProperty->setAccessible(true);
        $this->assertSame($basePath, $basePathProperty->getValue($middleware));
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
