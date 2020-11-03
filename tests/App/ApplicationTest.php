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

use PHPUnit\Framework\TestCase;
use Kovey\Web\App\Bootstrap\Autoload;
use Kovey\Web\Middleware\Cors;
use Kovey\Web\App\Http\Router\Routers;
use Kovey\Web\Server\Server;
use Kovey\Library\Container\Container;
use Kovey\Library\Container\ContainerInterface;
use Kovey\Library\Process\UserProcess;
use Kovey\Connection\Pool\Mysql;
use Kovey\Connection\Pool\PoolInterface;
use Kovey\Db\Adapter;
use Kovey\Library\Config\Manager;
use Swoole\Http\Request as SHR;
use Kovey\Web\App\Http\Request\Request;
use Kovey\Web\App\Http\Response\Response;
use Kovey\Web\App\Http\Request\RequestInterface;
use Kovey\Web\App\Http\Response\ResponseInterface;
use Kovey\Web\App\Mvc\View\Sample;
use Kovey\Web\App\Http\Pipeline\Pipeline;
use Kovey\Library\Util\Json;

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
        Application::getInstance()->on('console', function (string $path, string $method, Array $args) {
            $this->assertEquals('path', $path);
            $this->assertEquals('method', $method);
            $this->assertEquals(array('path', 'method'), $args);
        });

        Application::getInstance()->console('path', 'method', array('path', 'method'));
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
        Application::getInstance()->on('request', function ($req) {
            return new Request($req);
        });
        Application::getInstance()->on('response', function () {
            return new Response();
        });
        Application::getInstance()->on('pipeline', function ($req, $res, $router, $traceId) {
            return (new Pipeline(Application::getInstance()->getContainer()))
                ->via('handle')
                ->send($req, $res)
                ->through(array_merge(Application::getInstance()->getDefaultMiddlewares(), $router->getMiddlewares()))
                ->then(function (RequestInterface $req, ResponseInterface $res) use ($router, $traceId) {
                    return Application::getInstance()->runAction($req, $res, $router, $traceId);
                });
        });
        Application::getInstance()->on('view', function ($con, $template) {
            $con->setView(new Sample($con->getResponse(), $template));
        });
        $this->assertEquals(array(
            'httpCode' => 200,
            'content' => "<p>framework<p>\n<p>server<p>\n",
            'header' => array(
                'Server' => 'kovey framework',
                'Connection' => 'keep-alive',
                'Content-Type' => 'text/html; charset=utf-8',
                'Content-Length' => 29
            ),
            'cookie' => array()
        ), Application::getInstance()->workflow($this->req, hash('sha256', '123456')));
    }

    public function tearDown() : void
    {
        if (is_file(APPLICATION_PATH . '/logs/file.pid')) {
            unlink(APPLICATION_PATH . '/logs/file.pid');
        }
        if (is_file(APPLICATION_PATH . '/logs/server.log')) {
            unlink(APPLICATION_PATH . '/logs/server.log');
        }

        if (is_dir(APPLICATION_PATH . '/logs')) {
            rmdir(APPLICATION_PATH . '/logs');
        }
    }
}
