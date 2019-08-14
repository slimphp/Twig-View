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
     * @var string|null
     */
    protected $serverRequestAttributeName;

    /**
     * @param App    $app
     * @param string $containerKey
     *
     * @return TwigMiddleware
     */
    public static function createFromContainer(App $app, string $containerKey = 'view'): self
    {
        $container = $app->getContainer();
        if ($container === null) {
            throw new RuntimeException('The app does not have a container.');
        }
        if (!$container->has($containerKey)) {
            throw new RuntimeException(
                "The specified container key does not exist: $containerKey"
            );
        }

        $twig = $container->get($containerKey);
        if (!($twig instanceof Twig)) {
            throw new RuntimeException(
                "Twig instance could not be resolved via container key: $containerKey"
            );
        }

        return new self(
            $twig,
            $app->getRouteCollector()->getRouteParser(),
            $app->getBasePath()
        );
    }

    /**
     * @param Twig                 $twig
     * @param RouteParserInterface $routeParser
     * @param string               $basePath
     * @param string|null          $serverRequestAttributeName
     */
    public function __construct(
        Twig $twig,
        RouteParserInterface $routeParser,
        string $basePath = '',
        ?string $serverRequestAttributeName = null
    ) {
        $this->twig = $twig;
        $this->routeParser = $routeParser;
        $this->basePath = $basePath;
        $this->serverRequestAttributeName = $serverRequestAttributeName;
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

        if ($this->serverRequestAttributeName !== null) {
            $request = $request->withAttribute($this->serverRequestAttributeName, $this->twig);
        }

        return $handler->handle($request);
    }
}
