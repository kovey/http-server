<?php
/**
 * @description
 *
 * @package
 *
 * @author kovey
 *
 * @time 2020-11-02 13:20:11
 *
 */
namespace Kovey\Web\Middleware;

use PHPUnit\Framework\TestCase;
use Swoole\Http\Request as SHR;
use Kovey\Library\Util\Json;
use Kovey\Web\App\Http\Request\Request;
use Kovey\Web\App\Http\Response\Response;

class CorsTest extends TestCase
{
    protected $req;

    public function setUp() : void
    {
        $this->req = $this->createMock(SHR::class);
        $this->req->method('getContent')
             ->willReturn(Json::encode(array('kovey' => 'framework', 'http' => 'server', 'text' => '<script src="https://www.kovey.cn/js/test.js" type="text/javascript"></script>')));
        $this->req->method('getData')
             ->willReturn(array('kovey' => 'framework', 'http' => 'server', 'text' => '<script src="https://www.kovey.cn/js/test.js" type="text/javascript"></script>'));

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

    public function testCors()
    {
        $cors = new Cors();
        $this->assertTrue($cors->handle(new Request($this->req), new Response(), function ($request, $response) {
            $this->assertEquals('&lt;script src=&quot;https://www.kovey.cn/js/test.js&quot; type=&quot;text/javascript&quot;&gt;&lt;/script&gt;', $request->getQuery('text'));
            return true;
        }));
    }
}
