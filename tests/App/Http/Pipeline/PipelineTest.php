<?php
/**
 * @description
 *
 * @package
 *
 * @author kovey
 *
 * @time 2020-11-02 11:58:27
 *
 */
namespace Kovey\Web\App\Http\Pipeline;

use PHPUnit\Framework\TestCase;
use Kovey\Container\Container;
use Kovey\Library\Util\Json;
use Swoole\Http\Request as SHR;
use Kovey\Web\App\Http\Request\RequestInterface;
use Kovey\Web\App\Http\Request\Request;
use Kovey\Web\App\Http\Response\ResponseInterface;
use Kovey\Web\App\Http\Response\Response;

class PipelineTest extends TestCase
{
    protected $req;

    protected function setUp() : void
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

    public function testPipeline()
    {
        $response = new Response();
        $response->setBody(Json::encode(array('kovey' => 'framework')));
        $pipeline = new Pipeline(new Container());
        $pipeline->via('handle')
                 ->send(new Request($this->req), $response, hash('sha256', '123456'))
                 ->through(array())
                 ->then(function (RequestInterface $req, ResponseInterface $res) {
                     $this->assertEquals('/kovey/test/kovey/framework', $req->getUri());
                     $this->assertEquals("HTTP/1.1 200 OK\r\nServer: kovey framework\r\nConnection: keep-alive\r\nContent-Type: text/html; charset=utf-8\r\nContent-Length: 21\r\n\r\n", $res->getHeader());
                 });
    }
}
