<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @license   https://github.com/slimphp/Twig-View/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Mocks;

use ArrayAccess;
use Psr\Container\ContainerInterface;

class MockContainerWithArrayAccess implements ArrayAccess, ContainerInterface
{
    /**
     * @var array
     */
    private $data = [];

    public function get($id)
    {
        return $this->has($id) ? $this->data[$id] : null;
    }

    public function has($id)
    {
        return isset($this->data[$id]);
    }

    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }
}
