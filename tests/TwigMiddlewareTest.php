<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @license   https://github.com/slimphp/Twig-View/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests;

use DI\Container;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Interfaces\RouteParserInterface;
use Slim\Tests\Mocks\MockContainerWithArrayAccess;
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

    public function testProcessAppendsTwigExtensionToContainerWithSetMethod()
    {
        $basePath = '/base-path';
        $uriProphecy = $this->prophesize(UriInterface::class);
        $twigProphecy = $this->createTwigProphecy($uriProphecy, $basePath);
        $routeParserProphecy = $this->prophesize(RouteParserInterface::class);
        $container = new Container();

        /** @noinspection PhpParamsInspection */
        $twigMiddleware = new TwigMiddleware(
            $twigProphecy->reveal(),
            $container,
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

        $this->assertSame($twigProphecy->reveal(), $container->get('view'));
    }

    public function testProcessAppendsTwigExtensionToContainerWithArrayAccess()
    {
        $basePath = '/base-path';
        $uriProphecy = $this->prophesize(UriInterface::class);
        $twigProphecy = $this->createTwigProphecy($uriProphecy, $basePath);
        $routeParserProphecy = $this->prophesize(RouteParserInterface::class);
        $container = new MockContainerWithArrayAccess();

        /** @noinspection PhpParamsInspection */
        $twigMiddleware = new TwigMiddleware(
            $twigProphecy->reveal(),
            $container,
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

        $this->assertSame($twigProphecy->reveal(), $container->get('view'));
    }

    public function testSetContainerKey()
    {
        $basePath = '/base-path';
        $uriProphecy = $this->prophesize(UriInterface::class);
        $twigProphecy = $this->createTwigProphecy($uriProphecy, $basePath);
        $routeParserProphecy = $this->prophesize(RouteParserInterface::class);
        $container = new MockContainerWithArrayAccess();

        /** @noinspection PhpParamsInspection */
        $twigMiddleware = new TwigMiddleware(
            $twigProphecy->reveal(),
            $container,
            $routeParserProphecy->reveal(),
            $basePath
        );

        $twigMiddleware->setContainerKey(Twig::class);

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

        $this->assertSame($twigProphecy->reveal(), $container->get(Twig::class));
    }
}
