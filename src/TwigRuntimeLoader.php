<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @license   https://github.com/slimphp/Twig-View/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Views;

use Psr\Http\Message\UriInterface;
use Slim\Interfaces\RouteParserInterface;
use Twig\RuntimeLoader\RuntimeLoaderInterface;

class TwigRuntimeLoader implements RuntimeLoaderInterface
{
    /**
     * @var RouteParserInterface
     */
    protected $routeParser;

    /**
     * @var string
     */
    protected $basePath = '';

    /**
     * @var UriInterface
     */
    protected $uri;

    /**
     * TwigRuntimeLoader constructor.
     *
     * @param RouteParserInterface $routeParser
     * @param UriInterface         $uri
     * @param string               $basePath
     */
    public function __construct(RouteParserInterface $routeParser, UriInterface $uri, string $basePath = '')
    {
        $this->routeParser = $routeParser;
        $this->uri = $uri;
        $this->basePath = $basePath;
    }

    /**
     * {@inheritdoc}
     */
    public function load($class)
    {
        file_put_contents('C:/Users/Adrian/Desktop/test.log', $class);
        if (TwigRuntimeExtension::class === $class) {
            return new $class($this->routeParser, $this->uri, $this->basePath);
        }

        return null;
    }
}
