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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionProperty;
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
        $twig = $this->createMock(Twig::class);
        $routeParser = $this->createMock(RouteParserInterface::class);
        $basePath = '/base-path';
        $attribute = 'view';

        $routeCollector = $this->createMock(RouteCollectorInterface::class);
        $routeCollector->method('getRouteParser')->willReturn($routeParser);

        $app = $this->createMock(App::class);
        $app->method('getRouteCollector')->willReturn($routeCollector);
        $app->method('getBasePath')->willReturn($basePath);

        $middleware = TwigMiddleware::create($app, $twig, $attribute);

        $twigProperty = new ReflectionProperty(TwigMiddleware::class, 'twig');
        $twigProperty->setAccessible(true);
        $this->assertSame($twig, $twigProperty->getValue($middleware));

        $routeParserProperty = new ReflectionProperty(TwigMiddleware::class, 'routeParser');
        $routeParserProperty->setAccessible(true);
        $this->assertSame($routeParser, $routeParserProperty->getValue($middleware));

        $basePathProperty = new ReflectionProperty(TwigMiddleware::class, 'basePath');
        $basePathProperty->setAccessible(true);
        $this->assertSame($basePath, $basePathProperty->getValue($middleware));

        $attributeProperty = new ReflectionProperty(TwigMiddleware::class, 'attribute');
        $attributeProperty->setAccessible(true);
        $this->assertSame($attribute, $attributeProperty->getValue($middleware));
    }

    public function testProcessWithoutAttribute()
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

    public function testProcessWithAttribute()
    {
        $basePath = '/base-path';
        $attribute = 'view';
        $uriProphecy = $this->prophesize(UriInterface::class);
        $twigProphecy = $this->createTwigProphecy($uriProphecy, $basePath);
        $routeParserProphecy = $this->prophesize(RouteParserInterface::class);

        /** @noinspection PhpParamsInspection */
        $twigMiddleware = new TwigMiddleware(
            $twigProphecy->reveal(),
            $routeParserProphecy->reveal(),
            $basePath,
            $attribute
        );

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $attrRequestProphecy = $this->prophesize(ServerRequestInterface::class);

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        /** @noinspection PhpUndefinedMethodInspection */
        $requestProphecy
            ->getUri()
            ->willReturn($uriProphecy->reveal())
            ->shouldBeCalledOnce();
        $requestProphecy
            ->withAttribute($attribute, $twigProphecy->reveal())
            ->willReturn($attrRequestProphecy->reveal())
            ->shouldBeCalledOnce();

        $requestHandlerProphecy = $this->prophesize(RequestHandlerInterface::class);
        /** @noinspection PhpUndefinedMethodInspection */
        $requestHandlerProphecy
            ->handle($attrRequestProphecy->reveal())
            ->shouldBeCalledOnce()
            ->willReturn($responseProphecy->reveal());

        /** @noinspection PhpParamsInspection */
        $twigMiddleware->process($requestProphecy->reveal(), $requestHandlerProphecy->reveal());
    }
}
