<?php
/**
 * @description
 *
 * @package
 *
 * @author kovey
 *
 * @time 2020-10-30 09:37:48
 *
 */
namespace Kovey\Web\App;

use Swoole\Coroutine\System;
use PHPUnit\Framework\TestCase;
use Kovey\Web\App\Bootstrap\Autoload;
use Kovey\Web\Middleware\Cors;
use Kovey\Web\App\Http\Router\Routers;
use Kovey\Web\Server\Server;
use Kovey\Container\Container;
use Kovey\Container\ContainerInterface;
use Kovey\Process\UserProcess;
use Kovey\Connection\Pool\Mysql;
use Kovey\Connection\Pool\PoolInterface;
use Kovey\Db\Adapter;
use Kovey\Library\Config\Manager;
use Swoole\Http\Request as SHR;
use Swoole\Http\Response as SHS;
use Kovey\Web\App\Http\Request\Request;
use Kovey\Web\App\Http\Response\Response;
use Kovey\Web\App\Http\Request\RequestInterface;
use Kovey\Web\App\Http\Response\ResponseInterface;
use Kovey\Web\App\Mvc\View\Sample;
use Kovey\Pipeline\Pipeline;
use Kovey\Library\Util\Json;
use Kovey\Web\Event;
use Kovey\App\Event\Console;
use Kovey\App\Event\Monitor;

class ApplicationTest extends TestCase
{
    protected $req;

    public static function setUpBeforeClass() : void
    {
        Manager::init(APPLICATION_PATH . '/conf/');
        Application::getInstance(Manager::get('framework.app'));
    }

    public function setUp() : void
    {
        $this->res = $this->createMock(SHS::class);
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
            'request_uri' => '/kovey/test',
            'request_method' => 'GET',
            'REMOTE_ADDR' => '127.0.0.1'
        );
        $this->req->post = array();
        $this->req->get = array();
        $this->req->cookie = array();
        $this->req->files = array();
    }

    public function testRegisterGetGlobal()
    {
        $this->assertInstanceOf(Application::class, Application::getInstance()->registerGlobal('test', 'kovey'));
        $this->assertInstanceOf(Application::class, Application::getInstance()->registerGlobal('http', 1));

        $this->assertEquals('kovey', Application::getInstance()->getGlobal('test'));
        $this->assertEquals(1, Application::getInstance()->getGlobal('http'));
    }

    public function testRegisterAutoload()
    {
        $autoload = new Autoload();
        $autoload->register();
        $this->assertInstanceOf(Application::class, Application::getInstance()->registerAutoload($autoload));
    }

    public function testRegisterGetMiddleware()
    {
        $cors = new Cors();
        $this->assertInstanceOf(Application::class, Application::getInstance()->registerMiddleware($cors));
        $this->assertEquals(Array($cors), Application::getInstance()->getDefaultMiddlewares());
    }

    public function testRegisterRouters()
    {
        $this->assertInstanceOf(Application::class, Application::getInstance()->registerRouters(new Routers()));
    }

    public function testRegisterServer()
    {
        $server = $this->createMock(Server::class);
        $server->method('on')
            ->willReturn($server);
        $this->assertInstanceOf(Application::class, Application::getInstance()->registerServer($server));
    }

    public function testRegisterConsole()
    {
        Application::getInstance()->on('console', function (Console $event) {
            $this->assertEquals('path', $event->getPath());
            $this->assertEquals('method', $event->getMethod());
            $this->assertEquals(array('path', 'method'), $event->getArgs());
            $this->assertEquals(hash('sha256', '123456'), $event->getTraceId());
        });

        $console = new Console('path', 'method', array('path', 'method'), hash('sha256', '123456'));
        Application::getInstance()->console($console);
    }

    public function testRegisterContainer()
    {
        $this->assertInstanceOf(Application::class, Application::getInstance()->registerContainer(new Container()));
        $this->assertInstanceOf(ContainerInterface::class, Application::getInstance()->getContainer());
    }

    public function testRegisterUserProcess()
    {
        $this->assertInstanceOf(Application::class, Application::getInstance()->registerUserProcess(new UserProcess(4)));
        $this->assertInstanceOf(UserProcess::class, Application::getInstance()->getUserProcess());
    }

    public function testRegisterPool()
    {
        $pool = new Mysql(array(
            'min' => 1,
            'max' => 2
        ), array(
            'dbname' => 'test',
            'host' => '127.0.0.1',
            'username' => 'root',
            'password' => '',
            'port' => 3306,
            'charset' => 'UTF8',
            'adapter' => Adapter::DB_ADAPTER_PDO,
            'options' => array()
        ));
        $this->assertInstanceOf(Application::class, Application::getInstance()->registerPool($pool::getWriteName(), $pool));
        $this->assertInstanceOf(PoolInterface::class, Application::getInstance()->getPool($pool::getWriteName()));
        $this->assertEquals(null, Application::getInstance()->getPool('test'));
    }

    public function testWokerflow()
    {
        Application::getInstance()->on('request', function (Event\Request $event) {
            return new Request($event->getRequest());
        });
        Application::getInstance()->on('response', function (Event\Response $event) {
            return new Response();
        });
        Application::getInstance()->on('pipeline', function (Event\Pipeline $event) {
            return (new Pipeline(Application::getInstance()->getContainer()))
                ->via('handle')
                ->send($event)
                ->through(array_merge(Application::getInstance()->getDefaultMiddlewares(), $event->getRouter()->getMiddlewares()))
                ->then(function (Event\Pipeline $event) {
                    return Application::getInstance()->runAction($event);
                });
        });
        Application::getInstance()->on('view', function (Event\View $event) {
            $event->getController()->setView(new Sample($event->getController()->getResponse(), $event->getTemplate()));
        });

        $workflow = new Event\Workflow($this->req, $this->res, hash('sha256', '123456'));
        $this->assertEquals(array(
            'httpCode' => 200,
            'content' => "<p>framework<p>\n<p>server<p>\n",
            'header' => array(
                'Server' => 'kovey framework',
                'Connection' => 'keep-alive',
                'Content-Type' => 'text/html; charset=utf-8',
                'Content-Length' => 29
            ),
            'cookie' => array(),
            'class' => 'koveyController',
            'method' => 'testAction'
        ), Application::getInstance()->workflow($workflow));
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
