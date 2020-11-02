<?php
/**
 * @description
 *
 * @package
 *
 * @author kovey
 *
 * @time 2020-11-02 09:36:55
 *
 */
namespace Kovey\Web\App\Http\Request;

use PHPUnit\Framework\TestCase;
use Swoole\Http\Request as SHR;
use Kovey\Library\Util\Json;

class RequestTest extends TestCase
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

    public function testRequest()
    {
        $request = new Request($this->req);
        $request->setAction('test')
            ->setController('kovey');
        $this->assertTrue($request->isWebSocket());
        $this->assertEquals('127.0.0.1', $request->getClientIP());
        $this->assertEquals('Firefox(81.0)', $request->getBrowser());
        $this->assertEquals('Macintosh', $request->getOS());
        $this->assertEquals(array('kovey' => 'framework', 'http' => 'server'), $request->getQuery());
        $this->assertEquals(array(), $request->getPost());
        $this->assertEquals(array(), $request->getDelete());
        $this->assertEquals(array(), $request->getPut());
        $this->assertEquals('get', $request->getMethod());
        $this->assertEquals('/kovey/test/kovey/framework', $request->getUri());
        $this->assertEquals('framework', $request->getParam('kovey'));
        $this->assertEquals('localhost', $request->getBaseUrl());
        $this->assertEquals('test', $request->getAction());
        $this->assertEquals('kovey', $request->getController());
        $this->assertEquals('{"kovey":"framework","http":"server"}', $request->getPhpinput());
        $this->assertEquals(array(), $request->getCookie());
        $this->assertEquals('localhost', $request->getHeader('Host'));
        $this->assertEquals(array(), $request->getFiles());
    }
}
