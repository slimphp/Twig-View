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
            new \Twig_SimpleFunction('base_path', array($this, 'basePath')),
            new \Twig_SimpleFunction('is_current_path', array($this, 'isCurrentPath')),
        ];
    }

    /**
     * Get path for named route
     *
     * @param $name Name of route
     * @param array $data ?
     * @param array $queryParams ?
     * @param string $appName ?
     * @return string Path for named routes\
     */
    public function pathFor($name, $data = [], $queryParams = [], $appName = 'default')
    {
        return $this->router->pathFor($name, $data, $queryParams);
    }

    /**
     * Get base URL i.e. http://www.slimframework.com/
     *
     * @return string
     */
    public function baseUrl()
    {
        return $this->indexClean($this->uri->getBaseUrl());
    }

    /**
     * Get base_path i.e. working directory of Slim index.php
     *
     * @return string
     */
    public function basePath()
    {
        return $this->indexClean($this->uri->getBasePath());
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
     * Remove index.php from base_url | base_path
     *
     * @param string $base Getting base_url | base_path
     * @return string Filtered base_url | base_path
     */
    public function indexClean($base)
    {
        $replaces = ['index.php', 'index.php?'];
        return rtrim(str_ireplace($replaces, '', $base), '/');
    }
}