<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @license   https://github.com/slimphp/Twig-View/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Views;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Slim\App;
use Slim\Interfaces\RouteParserInterface;

class LazyTwigMiddleware implements MiddlewareInterface
{
    /**
     * @var RouteParserInterface
     */
    protected $routeParser;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var string
     */
    protected $containerKey;

    /**
     * @var string
     */
    protected $basePath;

    /**
     * @param App    $app
     * @param string $containerKey
     *
     * @return TwigMiddleware
     */
    public static function create(App $app, string $containerKey = 'view'): self
    {
        $container = $app->getContainer();
        if ($container === null) {
            throw new RuntimeException('The app does not have a container.');
        }

        return new self(
            $app->getRouteCollector()->getRouteParser(),
            $container,
            $containerKey,
            $app->getBasePath()
        );
    }

    /**
     * @param RouteParserInterface $routeParser
     * @param ContainerInterface   $container
     * @param string               $containerKey
     * @param string               $basePath
     */
    public function __construct(
        RouteParserInterface $routeParser,
        ContainerInterface $container,
        string $containerKey = 'view',
        string $basePath = ''
    ) {
        $this->routeParser = $routeParser;
        $this->container = $container;
        $this->containerKey = $containerKey;
        $this->basePath = $basePath;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $twig = $this->container->get($this->containerKey);

        $runtimeLoader = new TwigRuntimeLoader($this->routeParser, $request->getUri(), $this->basePath);
        $twig->addRuntimeLoader($runtimeLoader);

        $extension = new TwigExtension();
        $twig->addExtension($extension);

        return $handler->handle($request);
    }
}
