<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @license   https://github.com/slimphp/Twig-View/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Views;

use ArrayAccess;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Interfaces\RouteParserInterface;

class TwigMiddleware implements MiddlewareInterface
{
    /**
     * @var Twig
     */
    protected $twig;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var RouteParserInterface
     */
    protected $routeParser;

    /**
     * @var string
     */
    protected $basePath;

    /**
     * @var string
     */
    protected $containerKey;

    /**
     * @param Twig                 $twig
     * @param ContainerInterface   $container
     * @param RouteParserInterface $routeParser
     * @param string               $basePath
     * @param string               $containerKey
     */
    public function __construct(
        Twig $twig,
        ContainerInterface $container,
        RouteParserInterface $routeParser,
        string $basePath = '',
        string $containerKey = 'view'
    ) {
        $this->twig = $twig;
        $this->container = $container;
        $this->routeParser = $routeParser;
        $this->basePath = $basePath;
        $this->containerKey = $containerKey;
    }

    /**
     * Set the container key.
     *
     * @param string $containerKey
     */
    public function setContainerKey(string $containerKey): void
    {
        $this->containerKey = $containerKey;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $runtimeLoader = new TwigRuntimeLoader($this->routeParser, $request->getUri(), $this->basePath);
        $this->twig->addRuntimeLoader($runtimeLoader);

        $extension = new TwigExtension();
        $this->twig->addExtension($extension);

        if (method_exists($this->container, 'set')) {
            $this->container->set($this->containerKey, $this->twig);
        } elseif ($this->container instanceof ArrayAccess) {
            $this->container[$this->containerKey] = $this->twig;
        }

        return $handler->handle($request);
    }
}
