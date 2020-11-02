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
namespace Kovey\Web\App\Http\Response;

use PHPUnit\Framework\TestCase;
use Kovey\Library\Util\Json;

class ResponseTest extends TestCase
{

    public function testResponse()
    {
        $response = new Response();
        $response->noCache();
        $response->setBody(Json::encode(array('kovey' => 'framework')));
        $this->assertInstanceOf(ResponseInterface::class, $response->status(200));
        $this->assertInstanceOf(ResponseInterface::class, $response->redirect('/login/login'));
        $this->assertInstanceOf(ResponseInterface::class, $response->setHeader('Content-Type', 'application/json'));
        $this->assertInstanceOf(ResponseInterface::class, $response->setCookie('session_id', md5('123456')));
        $this->assertInstanceOf(ResponseInterface::class, $response->addHeaders(array('Access-Token' => '123456')));
        $this->assertEquals(302, $response->getHttpCode());
        $this->assertEquals(array(
            'httpCode' => 302,
            'content' => '{"kovey":"framework"}',
            'header' => array(
                'Server' => 'kovey framework',
                'Connection' => 'keep-alive',
                'Content-Type' => 'application/json',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0',
                'Pragma' => 'no-cache',
                'Content-Length' => 21,
                'Location' => '/login/login',
                'Access-Token' => '123456'
            ),
            'cookie' => array(
                'session_id=' . md5('123456') . '; path=/'
            )
        ), $response->toArray());
        $this->assertEquals(array(
            'session_id=' . md5('123456') . '; path=/'
        ), $response->getCookie());
        $this->assertEquals(array(
            'Server' => 'kovey framework',
            'Connection' => 'keep-alive',
            'Content-Type' => 'application/json',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0',
            'Pragma' => 'no-cache',
            'Content-Length' => 21,
            'Location' => '/login/login',
            'Access-Token' => '123456'
        ), $response->getHead());
        $this->assertEquals(Json::encode(array('kovey' => 'framework')), $response->getBody());
        $this->assertEquals("HTTP/1.1 200 OK\r\nServer: kovey framework\r\nConnection: keep-alive\r\nContent-Type: application/json\r\n" .
            "Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0\r\nPragma: no-cache\r\nContent-Length: 21\r\n" . 
            "Location: /login/login\r\nAccess-Token: 123456\r\nSet-Cookie: session_id=e10adc3949ba59abbe56e057f20f883e; path=/\r\n\r\n", $response->getHeader());
        $response->clearBody();
        $this->assertEquals('', $response->getBody());
        $this->assertEquals("HTTP/1.1 200 OK\r\nServer: kovey framework\r\nConnection: keep-alive\r\nContent-Type: application/json\r\n" .
            "Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0\r\nPragma: no-cache\r\nContent-Length: 0\r\n" . 
            "Location: /login/login\r\nAccess-Token: 123456\r\nSet-Cookie: session_id=e10adc3949ba59abbe56e057f20f883e; path=/\r\n\r\n", $response->getHeader());
        $this->assertEquals(array(
            'Server' => 'kovey framework',
            'Connection' => 'keep-alive',
            'Content-Type' => 'application/json',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0',
            'Pragma' => 'no-cache',
            'Content-Length' => 0,
            'Location' => '/login/login',
            'Access-Token' => '123456'
        ), $response->getHead());
    }
}
