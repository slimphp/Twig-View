<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @license   https://github.com/slimphp/Twig-View/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Views;

use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

class TwigContext
{
    /**
     * @param ServerRequestInterface $serverRequest
     * @param string                 $attributeName
     *
     * @return Twig
     */
    public static function fromRequest(ServerRequestInterface $serverRequest, string $attributeName = 'view'): Twig
    {
        $twig = $serverRequest->getAttribute($attributeName);
        if ($twig === null) {
            throw new RuntimeException(sprintf(
                'Twig could not be found in the server request attributes using the key "%s".',
                $attributeName
            ));
        }

        return $twig;
    }
}
