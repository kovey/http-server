<?php
/**
 * @description
 *
 * @package
 *
 * @author kovey
 *
 * @time 2020-11-02 12:26:58
 *
 */
namespace Kovey\Web\App\Mvc\Controller;

use PHPUnit\Framework\TestCase;
use Swoole\Http\Request as SHR;
use Kovey\Library\Util\Json;
use Kovey\Web\App\Http\Request\Request;
use Kovey\Web\App\Http\Response\Response;
use Kovey\Web\App\Http\Response\ResponseInterface;
use Kovey\Web\App\Http\Request\RequestInterface;
use Kovey\Web\App\Mvc\View\Sample;

class ControllerTest extends TestCase
{
    protected $req;

    public function setUp() : void
    {
        $this->req = $this->createMock(SHR::class);
        $this->req->method('getContent')
             ->willReturn(Json::encode(array('kovey' => 'framework', 'http' => 'server')));
        $this->req->method('getData')
             ->willReturn(array('kovey' => 'framework', 'http' => 'server'));

        $this->req->header = array(
            'content-type' => 'application/json',
            'host' => 'localhost',
            'upgrade' => 'websocket',
            'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:81.0) Gecko/20100101 Firefox/81.0'
        );
        $this->req->server = array();
        foreach ($_SERVER as $key => $val) {
            $this->req->server[strtolower($key)] = $val;
        }
        $this->req->server = array(
            'request_uri' => '/kovey/test/kovey/framework',
            'request_method' => 'GET',
            'REMOTE_ADDR' => '127.0.0.1'
        );
        $this->req->post = array();
        $this->req->get = array();
        $this->req->cookie = array();
        $this->req->files = array();
    }

    public function testController()
    {
        $response = new Response();
        $controller = new Controller(new Request($this->req), $response, array());
        $view = new Sample($response, APPLICATION_PATH . '/application/views/sample/index.phtml');
        $view->kovey = $controller->getRequest()->getQuery('kovey');
        $controller->setView($view);
        $controller->render();
        $controller->setHeader('Content-Type', 'Text/Html');
        $controller->redirect('/login/login');
        $this->assertFalse($controller->isPluginDisabled());
        $controller->disablePlugin();
        $this->assertTrue($controller->isPluginDisabled());
        $this->assertFalse($controller->isViewDisabled());
        $controller->disableView();
        $this->assertTrue($controller->isViewDisabled());
        $this->assertEquals(array(), $controller->getPlugins());
        $this->assertInstanceOf(ResponseInterface::class, $controller->getResponse());
        $this->assertInstanceOf(RequestInterface::class, $controller->getRequest());
        $this->assertEquals("HTTP/1.1 200 OK\r\nServer: kovey framework\r\nConnection: keep-alive\r\nContent-Type: Text/Html\r\n" .
            "Content-Length: 16\r\nLocation: /login/login\r\n\r\n", $controller->getResponse()->getHeader());
        $this->assertEquals("<p>framework<p>\n", $controller->getResponse()->getBody());
    }
}
