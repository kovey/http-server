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
use Kovey\Web\App\Http\Response\ResponseInterface;
use Kovey\Web\Event\Pipeline;
use Kovey\Web\App\Http\Router\Router;
use Kovey\Validator\Rules;

class ValidatorTest extends TestCase
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

    public function testValidatorSuccess()
    {
        $validator = new Validator();
        $validator->setRules(array(
            new Rules\Required('kovey'), new Rules\MinLength('kovey', 1), new Rules\MaxLength('kovey', 20),
            new Rules\Required('http'), new Rules\MinLength('http', 1), new Rules\MaxLength('http', 20),
            new Rules\Required('text'), new Rules\MinLength('text', 1), new Rules\MaxLength('text', 128),
        ));
        $this->assertTrue($validator->handle(new Pipeline(new Request($this->req), new Response(), $this->createMock(Router::class), md5('aaa')), function ($event) {
            return true;
        }));
    }

    public function testValidatorFailure()
    {
        $traceId = md5('aaaa');
        $validator = new Validator();
        $validator->setRules(array(
            new Rules\Required('kovey'), new Rules\MinLength('kovey', 1), new Rules\MaxLength('kovey', 20),
            new Rules\Required('http'), new Rules\MinLength('http', 1), new Rules\MaxLength('http', 20),
            new Rules\Required('text'), new Rules\MinLength('text', 1), new Rules\MaxLength('text', 20),
        ));

        $result = $validator->handle(new Pipeline(new Request($this->req), new Response(), $this->createMock(Router::class), $traceId), function ($event) {
            return true;
        });
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals("HTTP/1.1 200 OK\r\nServer: kovey framework\r\nConnection: keep-alive\r\nContent-Type: text/html; charset=utf-8\r\n" . 
            "content-type: application/json\r\nContent-Length: 180\r\n\r\n", $result->getHeader());
        $this->assertEquals('{"code":1000,"msg":"[text] validate failure with MaxLength, value: [<script src=\"https://www.kovey.cn/js/test.js\" type=\"text/javascript\"></script>], condition: [20]","data":{}}', $result->getBody());
    }
}
