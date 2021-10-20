<?php

/**
 * Slim Framework (http://slimframework.com)
 *
 * @license   https://github.com/slimphp/Twig-View/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests;

use DateTimeImmutable;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Slim\Views\Twig;
use Twig\Extension\ExtensionInterface;
use Twig\Extension\RuntimeExtensionInterface;
use Twig\Loader\ArrayLoader;
use Twig\Loader\LoaderInterface;
use Twig\RuntimeLoader\RuntimeLoaderInterface;

class TwigTest extends TestCase
{
    public function testFromRequest()
    {
        $twig = $this->createMock(Twig::class);

        $serverRequestProphecy = $this->prophesize(ServerRequestInterface::class);
        $serverRequestProphecy->getAttribute('view')
            ->willReturn($twig);

        /** @var ServerRequestInterface $serverRequest */
        $serverRequest = $serverRequestProphecy->reveal();
        $this->assertSame($twig, Twig::fromRequest($serverRequest));
    }

    public function testFromRequestCustomAttributeName()
    {
        $twig = $this->createMock(Twig::class);

        $serverRequestProphecy = $this->prophesize(ServerRequestInterface::class);
        $serverRequestProphecy->getAttribute('foo')
            ->willReturn($twig);

        /** @var ServerRequestInterface $serverRequest */
        $serverRequest = $serverRequestProphecy->reveal();
        $this->assertSame($twig, Twig::fromRequest($serverRequest, 'foo'));
    }

    public function testFromRequestTwigNotFound()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Twig could not be found in the server request attributes using the key "view".');

        $serverRequestProphecy = $this->prophesize(ServerRequestInterface::class);
        $serverRequestProphecy->getAttribute('view')
            ->willReturn(null);

        /** @var ServerRequestInterface $serverRequest */
        $serverRequest = $serverRequestProphecy->reveal();
        Twig::fromRequest($serverRequest);
    }

    public function testFromRequestNotTwig()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Twig could not be found in the server request attributes using the key "view".');

        $serverRequestProphecy = $this->prophesize(ServerRequestInterface::class);
        $serverRequestProphecy->getAttribute('view')
            ->willReturn('twiggy');

        /** @var ServerRequestInterface $serverRequest */
        $serverRequest = $serverRequestProphecy->reveal();
        Twig::fromRequest($serverRequest);
    }

    public function testAddExtension()
    {
        $loader = $this->createMock(LoaderInterface::class);
        $view = new Twig($loader);

        $mock = $this->createMock(ExtensionInterface::class);
        $view->addExtension($mock);
        $this->assertTrue($view->getEnvironment()->hasExtension(get_class($mock)));
    }

    public function testAddRuntimeLoader()
    {
        $loader = $this->createMock(LoaderInterface::class);
        $view = new Twig($loader);

        // Mock a runtime extension.
        $runtimeExtension = $this->createMock(RuntimeExtensionInterface::class);

        // Mock a runtime loader.
        $runtimeLoader = $this->getMockBuilder(RuntimeLoaderInterface::class)
            ->onlyMethods(['load'])
            ->getMock();

        // The method `load` should be called once and should return the mocked runtime extension.
        $runtimeLoader->expects($this->once())
            ->method('load')
            ->willReturn($runtimeExtension);

        $view->addRuntimeLoader($runtimeLoader);

        $this->assertSame($runtimeExtension, $view->getEnvironment()->getRuntime(get_class($runtimeLoader)));
    }

    public function testFetch()
    {
        $loader = new ArrayLoader([
            'example.html' => "<p>Hi, my name is {{ name }}.</p>\n"
        ]);
        $view = new Twig($loader);

        $output = $view->fetch('example.html', [
            'name' => 'Josh'
        ]);

        $this->assertEquals("<p>Hi, my name is Josh.</p>\n", $output);
    }

    public function testFetchFromString()
    {
        $loader = $this->createMock(LoaderInterface::class);
        $view = new Twig($loader);

        $output = $view->fetchFromString("<p>Hi, my name is {{ name }}.</p>", [
            'name' => 'Josh'
        ]);

        $this->assertEquals("<p>Hi, my name is Josh.</p>", $output);
    }

    public function testFetchBlock()
    {
        $loader = new ArrayLoader([
            'block_example.html' => <<<EOF
{% block first %}<p>Hi, my name is {{name}}.</p>{% endblock %}
{% block second %}<p>My name is not {{name}}.</p>{% endblock %}
EOF
        ]);
        $view = new Twig($loader);

        $outputOne = $view->fetchBlock('block_example.html', 'first', [
            'name' => 'Josh'
        ]);

        $outputTwo = $view->fetchBlock('block_example.html', 'second', [
            'name' => 'Peter'
        ]);

        $this->assertEquals("<p>Hi, my name is Josh.</p>", $outputOne);
        $this->assertEquals("<p>My name is not Peter.</p>", $outputTwo);
    }

    public function testSingleNamespaceAndMultipleDirectories()
    {
        $weekday = (new DateTimeImmutable('2016-03-08'))->format('l');

        $view = Twig::create(
            [
                'namespace' => [
                    __DIR__ . '/another',
                    __DIR__ . '/templates',
                    __DIR__ . '/multi',
                ],
            ]
        );

        $anotherDirectory = $view->fetch('@namespace/example.html', [
            'name' => 'Peter'
        ]);

        $templatesDirectory = $view->fetch('@namespace/another_example.html', [
            'name'   => 'Peter',
            'gender' => 'male',
        ]);

        $outputMulti = $view->fetch('@namespace/directory/template/example.html', [
            'weekday' => $weekday,
        ]);

        $this->assertEquals("<p>Hi, my name is Peter.</p>\n", $anotherDirectory);
        $this->assertEquals("<p>Hi, my name is Peter and I am male.</p>\n", $templatesDirectory);
        $this->assertEquals('Happy Tuesday!', $outputMulti);
    }

    public function testArrayWithASingleTemplateWithANamespace()
    {
        $views = Twig::create([
            'One' => [
                __DIR__ . '/templates',
            ],
        ]);

        $output = $views->fetch('@One/example.html', [
            'name' => 'Josh'
        ]);

        $this->assertEquals("<p>Hi, my name is Josh.</p>\n", $output);
    }

    public function testASingleTemplateWithANamespace()
    {
        $views = Twig::create([
            'One' => __DIR__ . '/templates',
        ]);

        $output = $views->fetch('@One/example.html', [
            'name' => 'Josh'
        ]);

        $this->assertEquals("<p>Hi, my name is Josh.</p>\n", $output);
    }

    public function testMultipleTemplatesWithMultipleNamespace()
    {
        $weekday = (new DateTimeImmutable('2016-03-08'))->format('l');

        $views = Twig::create([
            'One'   => __DIR__ . '/templates',
            'Two'   => __DIR__ . '/another',
            'Three' => [
                __DIR__ . '/multi',
            ],
        ]);

        $outputOne = $views->fetch('@One/example.html', [
            'name' => 'Peter'
        ]);

        $outputTwo = $views->fetch('@Two/another_example.html', [
            'name'   => 'Peter',
            'gender' => 'male'
        ]);

        $outputThree = $views->fetch('@Three/directory/template/example.html', [
            'weekday' => $weekday,
        ]);

        $this->assertEquals("<p>Hi, my name is Peter.</p>\n", $outputOne);
        $this->assertEquals("<p>Hi, my name is Peter and I am male.</p>\n", $outputTwo);
        $this->assertEquals('Happy Tuesday!', $outputThree);
    }

    public function testMultipleDirectoriesWithoutNamespaces()
    {
        $weekday = (new DateTimeImmutable('2016-03-08'))->format('l');
        $view    = Twig::create([__DIR__ . '/multi/', __DIR__ . '/another/']);

        $rootDirectory = $view->fetch('directory/template/example.html', [
            'weekday' => $weekday,
        ]);
        $multiDirectory  = $view->fetch('another_example.html', [
            'name'   => 'Peter',
            'gender' => 'male',
        ]);

        $this->assertEquals('Happy Tuesday!', $rootDirectory);
        $this->assertEquals("<p>Hi, my name is Peter and I am male.</p>\n", $multiDirectory);
    }

    public function testRender()
    {
        $loader = new ArrayLoader([
            'example.html' => "<p>Hi, my name is {{ name }}.</p>\n"
        ]);
        $view = new Twig($loader);

        $mockBody = $this->getMockBuilder('Psr\Http\Message\StreamInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $mockResponse = $this->getMockBuilder('Psr\Http\Message\ResponseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $mockBody->expects($this->once())
            ->method('write')
            ->with("<p>Hi, my name is Josh.</p>\n")
            ->willReturn(28);

        $mockResponse->expects($this->once())
            ->method('getBody')
            ->willReturn($mockBody);

        $response = $view->render($mockResponse, 'example.html', [
            'name' => 'Josh'
        ]);
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testGetLoader()
    {
        $loader = $this->createMock(LoaderInterface::class);
        $view = new Twig($loader);
        $this->assertSame($loader, $view->getLoader());
    }

    public function testOffsetExists()
    {
        $loader = $this->createMock(LoaderInterface::class);
        $view = new Twig($loader);
        $view->offsetSet('foo', 'bar');
        $this->assertTrue($view->offsetExists('foo'));
        $this->assertFalse($view->offsetExists('moo'));
    }

    public function testArrayAccess()
    {
        $loader = $this->createMock(LoaderInterface::class);
        $view = new Twig($loader);
        $view->offsetSet('foo', 'bar');
        $this->assertEquals('bar', $view['foo']);
        $this->assertFalse(isset($view['moo']));
    }

    public function testOffsetGet()
    {
        $loader = $this->createMock(LoaderInterface::class);
        $view = new Twig($loader);
        $view->offsetSet('foo', 'bar');
        $this->assertEquals('bar', $view->offsetGet('foo'));
    }

    public function testOffsetGetUndefined()
    {
        $loader = $this->createMock(LoaderInterface::class);
        $view = new Twig($loader);
        $this->assertNull($view->offsetGet('foo'));
    }

    public function testOffsetUnset()
    {
        $loader = $this->createMock(LoaderInterface::class);
        $view = new Twig($loader);
        $view->offsetSet('foo', 'bar');
        $view->offsetUnset('foo');
        $this->assertFalse($view->offsetExists('foo'));
    }

    public function testCount()
    {
        $loader = $this->createMock(LoaderInterface::class);
        $view = new Twig($loader);
        $view->offsetSet('foo', 'bar');
        $this->assertEquals(1, $view->count());
    }

    public function testGetIterator()
    {
        $loader = $this->createMock(LoaderInterface::class);
        $view = new Twig($loader);
        $view->offsetSet('foo', 'bar');
        $iterator = $view->getIterator();
        $this->assertEquals(['foo' => 'bar'], $iterator->getArrayCopy());
    }
}
