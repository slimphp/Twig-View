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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Interfaces\RouteParserInterface;
use Slim\Tests\Mocks\MockContainerWithArrayAccess;
use Slim\Views\Twig;
use Slim\Views\TwigExtension;
use Slim\Views\TwigMiddleware;

class TwigMiddlewareTest extends TestCase
{
    public function testProcessAppendsTwigExtensionToContainerWithSetMethod()
    {
        $self = $this;

        $basePath = '/base-path';
        $uriProphecy = $this->prophesize(UriInterface::class);

        $twigProphecy = $this->prophesize(Twig::class);
        $twigProphecy
            ->addExtension(Argument::type('object'))
            ->will(function ($args) use ($self, $uriProphecy, $basePath) {
                /** @var TwigExtension $extension */
                $extension = $args[0];

                $self->assertEquals($uriProphecy->reveal(), $extension->getUri());
                $self->assertEquals($basePath, $extension->getBasePath());
            })
            ->shouldBeCalledOnce();

        $routeParserProphecy = $this->prophesize(RouteParserInterface::class);
        $container = new Container();

        $twigMiddleware = new TwigMiddleware(
            $twigProphecy->reveal(),
            $container,
            $routeParserProphecy->reveal(),
            $basePath
        );

        $responseProphecy = $this->prophesize(ResponseInterface::class);

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy
            ->getUri()
            ->willReturn($uriProphecy->reveal())
            ->shouldBeCalledOnce();

        $requestHandlerProphecy = $this->prophesize(RequestHandlerInterface::class);
        $requestHandlerProphecy
            ->handle($requestProphecy->reveal())
            ->shouldBeCalledOnce()
            ->willReturn($responseProphecy->reveal());

        $twigMiddleware->process($requestProphecy->reveal(), $requestHandlerProphecy->reveal());

        $this->assertSame($twigProphecy->reveal(), $container->get('view'));
    }

    public function testProcessAppendsTwigExtensionToContainerWithArrayAccess()
    {
        $self = $this;

        $basePath = '/base-path';
        $uriProphecy = $this->prophesize(UriInterface::class);

        $twigProphecy = $this->prophesize(Twig::class);
        $twigProphecy
            ->addExtension(Argument::type('object'))
            ->will(function ($args) use ($self, $uriProphecy, $basePath) {
                /** @var TwigExtension $extension */
                $extension = $args[0];

                $self->assertEquals($uriProphecy->reveal(), $extension->getUri());
                $self->assertEquals($basePath, $extension->getBasePath());
            })
            ->shouldBeCalledOnce();

        $routeParserProphecy = $this->prophesize(RouteParserInterface::class);
        $container = new MockContainerWithArrayAccess();

        $twigMiddleware = new TwigMiddleware(
            $twigProphecy->reveal(),
            $container,
            $routeParserProphecy->reveal(),
            $basePath
        );

        $responseProphecy = $this->prophesize(ResponseInterface::class);

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy
            ->getUri()
            ->willReturn($uriProphecy->reveal())
            ->shouldBeCalledOnce();

        $requestHandlerProphecy = $this->prophesize(RequestHandlerInterface::class);
        $requestHandlerProphecy
            ->handle($requestProphecy->reveal())
            ->shouldBeCalledOnce()
            ->willReturn($responseProphecy->reveal());

        $twigMiddleware->process($requestProphecy->reveal(), $requestHandlerProphecy->reveal());

        $this->assertSame($twigProphecy->reveal(), $container->get('view'));
    }
}
