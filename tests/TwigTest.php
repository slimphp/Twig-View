<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim\Tests\Views;

use Slim\Views\Twig;

require dirname(__DIR__) . '/vendor/autoload.php';

class TwigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Twig
     */
    protected $view;

    public function setUp()
    {
        $this->view = new Twig(dirname(__FILE__) . '/templates');
    }

    public function testFetch()
    {
        $output = $this->view->fetch('example.html', [
            'name' => 'Josh'
        ]);

        $this->assertEquals("<p>Hi, my name is Josh.</p>\n", $output);
    }

    public function testRender()
    {
        $mock = $this->getMockBuilder('Slim\Http\Response')
            ->disableOriginalConstructor()
            ->setMethods(['write'])
            ->getMock();
        $mock->expects($this->once())
            ->method('write')
            ->with("<p>Hi, my name is Josh.</p>\n")
            ->willReturn($mock);

        $response = $this->view->render($mock, 'example.html', [
            'name' => 'Josh'
        ]);
        $this->assertInstanceOf('Slim\Http\Response', $response);
    }
}
