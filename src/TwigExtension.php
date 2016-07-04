<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Twig-View
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/slimphp/Twig-View/blob/master/LICENSE.md (MIT License)
 */
namespace Slim\Views;

class TwigExtension extends \Twig_Extension
{
    /**
     * @var \Slim\Interfaces\RouterInterface
     */
    private $router;

    /**
     * @var string|\Slim\Http\Uri
     */
    private $uri;

    public function __construct($router, $uri)
    {
        $this->router = $router;
        $this->uri = $uri;
    }

    public function getName()
    {
        return 'slim';
    }

    /**
     * Modified by Walter Allen, 2016
     * to utilize the helper functions originally available
     * to Slim 2 Framework users in the Slim/Views package
     * as seen at https://github.com/slimphp/Slim-Views
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('pathFor', array($this, 'pathFor')),
            new \Twig_SimpleFunction('baseUrl', array($this, 'baseUrl')),
            new \Twig_SimpleFunction('siteUrl', array($this, 'siteForUrl')),
            new \Twig_SimpleFunction('currentUrl', array($this, 'currentUrl'))
        ];
    }

    public function pathFor($name, $data = [], $queryParams = [], $appName = 'default')
    {
        return $this->router->pathFor($name, $data, $queryParams);
    }

    public function baseUrl()
    {
        if (is_string($this->uri)) {
            return $this->uri;
        }
        if (method_exists($this->uri, 'getBaseUrl')) {
            return $this->uri->getBaseUrl();
        }
    }

    public function siteForUrl($url)
    {
        return $this->baseurl() . '/' . ltrim($url, '/');
    }

    public function currentUrl($withQueryString = true, $appName = 'default')
    {
        $uri=$this->baseUrl() . $this->uri->getPath();
        return $uri;
    }

    /**
     * Set the base url
     *
     * @param string|Slim\Http\Uri $baseUrl
     * @return void
     */
    public function setBaseUrl($baseUrl)
    {
        $this->uri = $baseUrl;
    }
}
