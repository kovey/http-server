<?php
/**
 * @description
 *
 * @package
 *
 * @author kovey
 *
 * @time 2020-11-02 12:17:02
 *
 */
namespace Kovey\Web\App\Mvc\View;

use PHPUnit\Framework\TestCase;
use Kovey\Web\App\Http\Response\Response;
use Kovey\Library\Util\Json;

class SampleTest extends TestCase
{
    public function testView()
    {
        $response = new Response();
        $view = new Sample($response, APPLICATION_PATH . '/application/views/sample/index.phtml');
        $view->kovey = 'framework';
        $view->setTemplate(APPLICATION_PATH . '/application/views/sample/index.phtml');
        $view->render();
        $this->assertEquals($response, $view->getResponse());
        $this->assertEquals("HTTP/1.1 200 OK\r\nServer: kovey framework\r\nConnection: keep-alive\r\nContent-Type: text/html; charset=utf-8\r\nContent-Length: 16\r\n\r\n", $response->getHeader());
        $this->assertEquals("<p>framework<p>\n", $response->getBody());
    }
}
