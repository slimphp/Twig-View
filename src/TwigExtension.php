<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Twig-View
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/slimphp/Twig-View/blob/master/LICENSE.md (MIT License)
 */
namespace Slim\Views;

use \Slim\Router;
use \Slim\Http\Uri;

class TwigExtension extends \Twig_Extension
{
    /**
     * @var \Slim\Router
     */
    private $router;

    /**
     * @var \Slim\Http\Uri
     */
    private $uri;

    /**
     * @var string Base url from URI instance
     */
    protected $baseUrl;

    public function __construct(Router $router, Uri $uri)
    {
        $this->router = $router;
        $this->uri = $uri;
    }

    public function getName()
    {
        return 'slim';
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('path_for', array($this, 'pathFor')),
            new \Twig_SimpleFunction('base_url', array($this, 'baseUrl')),
            new \Twig_SimpleFunction('is_current_path', array($this, 'isCurrentPath')),
        ];
    }

    public function pathFor($name, $data = [], $queryParams = [], $appName = 'default')
    {
        return $this->router->pathFor($name, $data, $queryParams);
    }

    /**
     * Get base URL
     *
     * @return string
     */
    public function baseUrl()
    {
        if(isset($this->baseUrl)) {
            return $this->filterBaseUrl($this->baseUrl);
        } else {
            return $this->filterBaseUrl($this->uri->getBaseUrl());
        }
    }

    /**
     * Check if current route is current path
     *
     * @param $name Current route
     * @param array $data
     * @return bool
     */
    public function isCurrentPath($name, $data = [])
    {
        return $this->router->pathFor($name, $data) === $this->uri->getPath();
    }

    /**
     * Set the base url
     *
     * @param Slim\Http\Uri $baseUrl
     * @return void
     */
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    /**
     * Remove index.php from baseUrl
     *
     * @param string $baseUrl Getting base URL
     * @return string Filtered base url
     */
    public function filterBaseUrl($baseUrl)
    {
        return rtrim(str_ireplace('index.php', '', $baseUrl), '/');
    }
}