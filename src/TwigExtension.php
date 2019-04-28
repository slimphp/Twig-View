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
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TwigExtension extends AbstractExtension
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
     * @return string
     */
    public function getName()
    {
        return 'slim';
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('url_for', [$this, 'urlFor']),
            new TwigFunction('full_url_for', [$this, 'fullUrlFor']),
            new TwigFunction('base_url', [$this, 'getBaseUrl']),
            new TwigFunction('is_current_url', [$this, 'isCurrentUrl']),
            new TwigFunction('current_url', [$this, 'getCurrentUrl']),
        ];
    }

    /**
     * @param string $routeName
     * @param array  $data
     * @param array  $queryParams
     *
     * @return string
     */
    public function urlFor(string $routeName, array $data = [], $queryParams = [])
    {
        return $this->routeParser->urlFor($routeName, $data, $queryParams);
    }

    /**
     * @param string $routeName   Route placeholders
     * @param array  $data        Route placeholders
     * @param array  $queryParams
     *
     * @return string
     */
    public function fullUrlFor(string $routeName, array $data = [], array $queryParams = [])
    {
        return $this->routeParser->fullUrlFor($this->uri, $routeName, $data, $queryParams);
    }

    /**
     * @param string $routeName
     * @param array  $data
     *
     * @return bool
     */
    public function isCurrentUrl(string $routeName, $data = []): bool
    {
        $currentUrl = $this->basePath . $this->uri->getPath();
        $result = $this->routeParser->urlFor($routeName, $data) ;

        return $result === $currentUrl;
    }

    /**
     * Returns current path on given URI.
     *
     * @param bool $withQueryString
     *
     * @return string
     */
    public function getCurrentUrl($withQueryString = false)
    {
        $currentUrl = $this->basePath . $this->uri->getPath();
        $query = $this->uri->getQuery();

        if ($withQueryString && !empty($query)) {
            $currentUrl .= '?' . $query;
        }

        return $currentUrl;
    }

    /**
     * @return UriInterface
     */
    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    /**
     * @param UriInterface $uri
     *
     * @return self
     */
    public function setUri(UriInterface $uri): self
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     * @return string
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Set the base url
     *
     * @param string $basePath
     *
     * @return self
     */
    public function setBasePath($basePath): self
    {
        $this->basePath = $basePath;
        return $this;
    }
}
