<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @license   https://github.com/slimphp/Twig-View/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests;

use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Slim\Views\Twig;
use Slim\Views\TwigContext;

class TwigContextTest extends TestCase
{
    public function testFromRequest()
    {
        $twig = $this->createMock(Twig::class);

        $serverRequestProphecy = $this->prophesize(ServerRequestInterface::class);
        $serverRequestProphecy->getAttribute('view')
            ->willReturn($twig);

        /** @var ServerRequestInterface $serverRequest */
        $serverRequest = $serverRequestProphecy->reveal();
        $this->assertSame($twig, TwigContext::fromRequest($serverRequest));
    }

    public function testFromRequestCustomAttributeName()
    {
        $twig = $this->createMock(Twig::class);

        $serverRequestProphecy = $this->prophesize(ServerRequestInterface::class);
        $serverRequestProphecy->getAttribute('foo')
            ->willReturn($twig);

        /** @var ServerRequestInterface $serverRequest */
        $serverRequest = $serverRequestProphecy->reveal();
        $this->assertSame($twig, TwigContext::fromRequest($serverRequest, 'foo'));
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Twig could not be found in the server request attributes using the key "view".
     */
    public function testFromRequestTwigNotFound()
    {
        $serverRequestProphecy = $this->prophesize(ServerRequestInterface::class);
        $serverRequestProphecy->getAttribute('view')
            ->willReturn(null);

        /** @var ServerRequestInterface $serverRequest */
        $serverRequest = $serverRequestProphecy->reveal();
        TwigContext::fromRequest($serverRequest);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Twig could not be found in the server request attributes using the key "foo".
     */
    public function testFromRequestCustomAttributeNameTwigNotFound()
    {
        $serverRequestProphecy = $this->prophesize(ServerRequestInterface::class);
        $serverRequestProphecy->getAttribute('foo')
            ->willReturn(null);

        /** @var ServerRequestInterface $serverRequest */
        $serverRequest = $serverRequestProphecy->reveal();
        TwigContext::fromRequest($serverRequest, 'foo');
    }
}
