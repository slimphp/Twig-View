<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @license   https://github.com/slimphp/Twig-View/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Views;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
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
     * @var string
     */
    protected $attribute;

    /**
     * @param App    $app
     * @param Twig   $twig
     * @param string $attribute
     *
     * @return TwigMiddleware
     */
    public static function create(App $app, Twig $twig, string $attribute = ''): self
    {
        return new self(
            $twig,
            $app->getRouteCollector()->getRouteParser(),
            $app->getBasePath(),
            $attribute
        );
    }

    /**
     * @param Twig                 $twig
     * @param RouteParserInterface $routeParser
     * @param string               $basePath
     * @param string               $attribute
     */
    public function __construct(
        Twig $twig,
        RouteParserInterface $routeParser,
        string $basePath = '',
        string $attribute = ''
    ) {
        $this->twig = $twig;
        $this->routeParser = $routeParser;
        $this->basePath = $basePath;
        $this->attribute = $attribute;
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

        if ($this->attribute) {
            $request = $request->withAttribute($this->attribute, $this->twig);
        }

        return $handler->handle($request);
    }
}
