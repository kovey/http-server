<?php
/**
 * @description
 *
 * @package
 *
 * @author kovey
 *
 * @time 2020-11-02 13:51:53
 *
 */
namespace Kovey\Web\Server;

use PHPUnit\Framework\TestCase;
use Swoole\Coroutine\System;
use Swoole\Http\Server as SHS;
use Swoole\Http\Request as SHR;
use Swoole\Http\Response as SHRO;
use Kovey\Library\Util\Json;
use Kovey\Logger\Logger;
use Kovey\Web\Event;
use Kovey\Event\Exception\EventException;

class ServerTest extends TestCase
{
    protected static $server;

    protected $swoole;

    protected $red;

    protected $res;

    public static function setUpBeforeClass() : void
    {
        self::$server = new Server(array(
            'run_docker' => 'Off',
            'document_root' => 'test',
            'pid_file' => APPLICATION_PATH . '/logs/file.pid',
            'log_file' => APPLICATION_PATH . '/logs/server.log',
            'worker_num' => 4,
            'max_co' => 30000,
            'package_max_length' => '1M',
            'name' => 'test',
            'host' => '127.0.0.1',
            'port' => 9501,
            'logger_dir' => APPLICATION_PATH . '/logs'
        ));
    }

    public function setUp() : void
    {
        $this->swoole = $this->createMock(SHS::class);
        $this->res = $this->createMock(SHRO::class);
        $this->res->method('status')
            ->willReturn(null);
        $this->res->method('end')
             ->willReturn(null);
        $this->res->method('header')
            ->willReturn(null);

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

    public function testPipeMessage()
    {
        self::$server->on('console', function (Event\Console $console) {
            $this->assertEquals('path', $console->getPath());
            $this->assertEquals('method', $console->getMethod());
            $this->assertEquals(Array('kovey' => 'framework'), $console->getArgs());
        });

        $msg = new \Swoole\Server\PipeMessage();
        $msg->data = array(
            'p' => 'path',
            'm' => 'method',
            'a' => array('kovey' => 'framework')
        );
        self::$server->pipeMessage($this->swoole, $msg);
    }

    public function testManagerStart()
    {
        $this->assertEquals(null, self::$server->managerStart($this->swoole));
    }

    public function testWokerStart()
    {
        self::$server->on('init', function (Event\Init $init) {
            $this->assertInstanceOf(Server::class, $init->getServer());
        });

        $this->assertEquals(null, self::$server->workerStart($this->swoole, 1));
    }

    public function testRequest()
    {
        self::$server->on('workflow', function (Event\Workflow $event) {
            $this->assertEquals($this->req, $event->getRequest());
            return array();
        });
        self::$server->on('monitor', function (Event\Monitor $event) {
            $data = $event->getData();
            $this->assertTrue(is_array($data));
            $this->assertEquals('/kovey/test/kovey/framework', $data['path']);
            $this->assertEquals('{"kovey":"framework","http":"server"}', $data['params']);
            $this->assertEquals('', $data['ip']);
            $this->assertEquals(500, $data['http_code']);
            $this->assertEquals('<p style="text-align:center;">Kovey Frawework</p><p style="text-align:center">Internal Error!</p>', $data['response']);
            $this->assertTrue(!empty($data['traceId']));
        });

        self::$server->request($this->req, $this->res);
    }

    public function testRequestFailure()
    {
        $this->assertEquals(null, self::$server->request($this->req, $this->res));
    }

    public function testGetServ()
    {
        $this->assertInstanceOf(\Swoole\Http\Server::class, self::$server->getServ());
    }

    public function tearDown() : void
    { 
        System::sleep(0.1);

        if (is_dir(APPLICATION_PATH . '/logs')) {
            foreach (scandir(APPLICATION_PATH . '/logs') as $file) {
                if ($file == '.' || $file == '..') {
                    continue;
                }

                $path = APPLICATION_PATH . '/logs/' . $file;
                if (is_dir($path)) {
                    foreach (scandir($path) as $pf) {
                        if ($pf == '.' || $pf == '..') {
                            continue;
                        }

                        unlink($path . '/' . $pf);
                    }
                    rmdir($path);
                    continue;
                }

                unlink($path);
            }

            rmdir(APPLICATION_PATH . '/logs');
        }
    }
}
