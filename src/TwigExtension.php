<?php

/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Twig-View
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/slimphp/Twig-View/blob/master/LICENSE.md (MIT License)
 */

namespace Slim\Views;

use Psr\Http\Message\UriInterface;
use Slim\Interfaces\RouterInterface;

/**
 * Class TwigExtension
 * 
 * @package Slim\Views
 */
class TwigExtension extends \Twig_Extension
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var UriInterface
     */
    private $uri;

    public function __construct(RouterInterface $router, UriInterface $uri)
    {
        $this->router = $router;
        $this->uri = $uri;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'slim';
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('path_for', [$this, 'pathFor']),
            new \Twig_SimpleFunction('base_url', [$this, 'baseUrl']),
        ];
    }

    /**
     * @param $name
     * @param array $data
     * @param array $queryParams
     * @param string $appName
     * @return string
     */
    public function pathFor($name, array $data = [], array $queryParams = [], $appName = 'default')
    {
        return $this->router->pathFor($name, $data, $queryParams);
    }

    /**
     * @param string $path
     * @return string
     */
    public function baseUrl($path = '')
    {
        if (method_exists($this->uri, 'getBaseUrl')) {
            return $this->uri->getBaseUrl() . $path;
        }
    }
}
