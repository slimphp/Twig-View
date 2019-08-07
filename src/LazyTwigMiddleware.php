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

class LazyTwigMiddleware extends TwigMiddleware
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var string
     */
    protected $containerKey;

    /**
     * {@inheritdoc}
     */
    public static function create(App $app, string $containerKey = 'view'): parent
    {
        $container = $app->getContainer();
        self::checkContainer($container, $containerKey);

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
        $this->twig = $this->container->get($this->containerKey);
        return parent::process($request, $handler);
    }
}
