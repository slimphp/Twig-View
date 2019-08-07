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

class TwigMiddleware implements MiddlewareInterface
{
    /**
     * @var Twig
     */
    protected $twig;

    /**
     * @var RouteParserInterface
     */
    protected $routeParser;

    /**
     * @var string
     */
    protected $basePath;

    /**
     * @param App    $app
     * @param string $containerKey
     */
    protected function checkContainer(?ContainerInterface $container, string $containerKey)
    {
        if ($container === null) {
            throw new RuntimeException('The app does not have a container.');
        }
        if (!$container->has($containerKey)) {
            throw new RuntimeException("'$containerKey' is not set on the container.");
        }
    }

    /**
     * @param App    $app
     * @param string $containerKey
     *
     * @return TwigMiddleware
     */
    public static function create(App $app, string $containerKey = 'view'): self
    {
        $container = $app->getContainer();
        self::checkContainer($container, $containerKey);

        return new self(
            $container->get($containerKey),
            $app->getRouteCollector()->getRouteParser(),
            $app->getBasePath()
        );
    }

    /**
     * @param Twig                 $twig
     * @param RouteParserInterface $routeParser
     * @param string               $basePath
     */
    public function __construct(
        Twig $twig,
        RouteParserInterface $routeParser,
        string $basePath = ''
    ) {
        $this->twig = $twig;
        $this->routeParser = $routeParser;
        $this->basePath = $basePath;
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

        return $handler->handle($request);
    }
}
