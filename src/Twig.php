<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @license   https://github.com/slimphp/Twig-View/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Views;

use ArrayAccess;
use ArrayIterator;
use Psr\Http\Message\ResponseInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\ExtensionInterface;
use Twig\Loader\FilesystemLoader;
use Twig\Loader\LoaderInterface;

/**
 * Twig View
 *
 * This class is a Slim Framework view helper built on top of the Twig templating component.
 * Twig is a PHP component created by Fabien Potencier.
 *
 * @link http://twig.sensiolabs.org/
 */
class Twig implements ArrayAccess
{
    /**
     * Twig loader
     *
     * @var LoaderInterface
     */
    protected $loader;

    /**
     * Twig environment
     *
     * @var Environment
     */
    protected $environment;

    /**
     * Default view variables
     *
     * @var array
     */
    protected $defaultVariables = [];

    /**
     * @param string|array $path     Path(s) to templates directory
     * @param array        $settings Twig environment settings
     */
    public function __construct($path, $settings = [])
    {
        $this->loader = $this->createLoader(is_string($path) ? [$path] : $path);
        $this->environment = new Environment($this->loader, $settings);
    }

    /**
     * Proxy method to add an extension to the Twig environment
     *
     * @param ExtensionInterface $extension A single extension instance or an array of instances
     */
    public function addExtension(ExtensionInterface $extension)
    {
        $this->environment->addExtension($extension);
    }

    /**
     * Fetch rendered template
     *
     * @param  string $template Template pathname relative to templates directory
     * @param  array  $data     Associative array of template variables
     *
     * @throws LoaderError  When the template cannot be found
     * @throws SyntaxError  When an error occurred during compilation
     * @throws RuntimeError When an error occurred during rendering
     *
     * @return string
     */
    public function fetch(string $template, array $data = [])
    {
        $data = array_merge($this->defaultVariables, $data);

        return $this->environment->render($template, $data);
    }

    /**
     * Fetch rendered block
     *
     * @param  string $template Template pathname relative to templates directory
     * @param  string $block    Name of the block within the template
     * @param  array  $data     Associative array of template variables
     *
     * @return string
     */
    public function fetchBlock(string $template, string $block, array $data = [])
    {
        $data = array_merge($this->defaultVariables, $data);

        return $this->environment->loadTemplate($template)->renderBlock($block, $data);
    }

    /**
     * Fetch rendered string
     *
     * @param  string $string String
     * @param  array  $data   Associative array of template variables
     *
     * @return string
     */
    public function fetchFromString(string $string = '', array $data = [])
    {
        $data = array_merge($this->defaultVariables, $data);

        return $this->environment->createTemplate($string)->render($data);
    }

    /**
     * Output rendered template
     *
     * @param  ResponseInterface $response
     * @param  string            $template Template pathname relative to templates directory
     * @param  array             $data Associative array of template variables
     * @return ResponseInterface
     */
    public function render(ResponseInterface $response, string $template, array $data = [])
    {
         $response->getBody()->write($this->fetch($template, $data));

         return $response;
    }

    /**
     * Create a loader with the given path
     *
     * @param array $paths
     * @return FilesystemLoader
     */
    private function createLoader(array $paths)
    {
        $loader = new FilesystemLoader();

        foreach ($paths as $namespace => $path) {
            if (is_string($namespace)) {
                $loader->setPaths($path, $namespace);
            } else {
                $loader->addPath($path);
            }
        }

        return $loader;
    }

    /**
     * Return Twig loader
     *
     * @return LoaderInterface
     */
    public function getLoader(): LoaderInterface
    {
        return $this->loader;
    }

    /**
     * Return Twig environment
     *
     * @return Environment
     */
    public function getEnvironment(): Environment
    {
        return $this->environment;
    }

    /**
     * Does this collection have a given key?
     *
     * @param  string $key The data key
     *
     * @return bool
     */
    public function offsetExists($key)
    {
        return array_key_exists($key, $this->defaultVariables);
    }

    /**
     * Get collection item for key
     *
     * @param string $key The data key
     *
     * @return mixed The key's value, or the default value
     */
    public function offsetGet($key)
    {
        return $this->defaultVariables[$key];
    }

    /**
     * Set collection item
     *
     * @param string $key   The data key
     * @param mixed  $value The data value
     */
    public function offsetSet($key, $value)
    {
        $this->defaultVariables[$key] = $value;
    }

    /**
     * Remove item from collection
     *
     * @param string $key The data key
     */
    public function offsetUnset($key)
    {
        unset($this->defaultVariables[$key]);
    }

    /**
     * Get number of items in collection
     *
     * @return int
     */
    public function count()
    {
        return count($this->defaultVariables);
    }

    /**
     * Get collection iterator
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->defaultVariables);
    }
}
