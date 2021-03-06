<?php
/**
 * @description
 *
 * @package
 *
 * @author kovey
 *
 * @time 2020-10-30 14:51:22
 *
 */
namespace Kovey\Web\App\Http\Router;

use PHPUnit\Framework\TestCase;
use Kovey\Web\Middleware\Cors;

class RouterTest extends TestCase
{
    public function testRouterIndex()
    {
        $router = new Router('/', 'GET');
        $this->assertEquals('index', $router->getAction());
        $this->assertEquals('index', $router->getController());
        $this->assertEquals('/Index.php', $router->getClassPath());
        $this->assertEquals('indexAction', $router->getActionName());
        $this->assertEquals('IndexController', $router->getClassName());
        $this->assertEquals('/index/index', $router->getViewPath());
        $this->assertEquals('get', $router->getMethod());
        $this->assertEquals(null, $router->getCallable());
        $this->assertEquals('/', $router->getUri());
        $this->assertTrue($router->isValid());
    }
    
    public function testRouterIndexAction()
    {
        $router = new Router('/kovey', 'POST');
        $this->assertEquals('index', $router->getAction());
        $this->assertEquals('kovey', $router->getController());
        $this->assertEquals('/Kovey.php', $router->getClassPath());
        $this->assertEquals('indexAction', $router->getActionName());
        $this->assertEquals('KoveyController', $router->getClassName());
        $this->assertEquals('/kovey/index', $router->getViewPath());
        $this->assertEquals('post', $router->getMethod());
        $this->assertEquals(null, $router->getCallable());
        $this->assertEquals('/kovey', $router->getUri());
        $this->assertTrue($router->isValid());
    }

    public function testRouter()
    {
        $router = new Router('/kovey/test', 'PUT');
        $this->assertEquals('test', $router->getAction());
        $this->assertEquals('kovey', $router->getController());
        $this->assertEquals('/Kovey.php', $router->getClassPath());
        $this->assertEquals('testAction', $router->getActionName());
        $this->assertEquals('KoveyController', $router->getClassName());
        $this->assertEquals('/kovey/test', $router->getViewPath());
        $this->assertEquals('put', $router->getMethod());
        $this->assertEquals(null, $router->getCallable());
        $this->assertEquals('/kovey/test', $router->getUri());
        $this->assertTrue($router->isValid());
    }

    public function testRouterInValid()
    {
        $router = new Router('kovey/test', 'DELETE');
        $this->assertEquals('delete', $router->getMethod());
        $this->assertFalse($router->isValid());
    }

    public function testRouterCallback()
    {
        $fun = function () {
        };
        $router = new Router('/kovey/test', 'DELETE', $fun);
        $this->assertEquals('delete', $router->getMethod());
        $this->assertEquals($fun, $router->getCallable());
    }

    public function testRouterMiddleware()
    {
        $router = new Router('/kovey/test', 'GET');
        $this->assertEquals('get', $router->getMethod());
        $cors = new Cors();
        $router->addMiddleware($cors);
        $this->assertEquals(array($cors), $router->getMiddlewares());
    }
}
