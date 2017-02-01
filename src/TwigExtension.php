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

    public function baseUrl()
    {
        if (method_exists($this->uri, 'getBaseUrl')) {
            // remove index.php if exist
            return $this->isIndex($this->uri->getBaseUrl());
        }
    }
    
    public function isIndex($uri) {

        if (empty($uri)) {
            return;
        }

        $checkurl = explode("/", $uri);
        $popindex = array_pop($checkurl);
        $return = ""; 
        if ($popindex <> "index.php") {
            $return = $uri;
        } elseif ($popindex == "index.php") {
            $return = implode("/", $checkurl);
        }
        return $return;
    }

    public function isCurrentPath($name)
    {
        return $this->router->pathFor($name) === $this->uri->getPath();
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
