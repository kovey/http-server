<?php
/**
 * @description
 *
 * @package
 *
 * @author kovey
 *
 * @time 2020-10-30 15:52:38
 *
 */
namespace Kovey\Web\App\Http\Router;

use PHPUnit\Framework\TestCase;

class RoutersTest extends TestCase
{
    public function testGETRouter()
    {
        $routers = new Routers();
        $this->assertInstanceOf(RoutersInterface::class, $routers->addRouter('/kovey/test', new Router('/kovey/test', 'GET')));
        $this->assertInstanceOf(RouterInterface::class, $routers->getRouter('/kovey/test', 'get'));
        $this->assertInstanceOf(RouterInterface::class, $routers->getRouter('/kovey/test', 'post'));
        $routers->disableDefault();
        $this->assertEquals(null, $routers->getRouter('/kovey/test', 'post'));
    }

    public function testPOSTRouter()
    {
        $routers = new Routers();
        $this->assertInstanceOf(RoutersInterface::class, $routers->addRouter('/kovey/test', new Router('/kovey/test', 'POST')));
        $this->assertInstanceOf(RouterInterface::class, $routers->getRouter('/kovey/test', 'post'));
        $this->assertInstanceOf(RouterInterface::class, $routers->getRouter('/kovey/test', 'get'));
        $routers->disableDefault();
        $this->assertEquals(null, $routers->getRouter('/kovey/test', 'get'));
    }

    public function testPUTRouter()
    {
        $routers = new Routers();
        $this->assertInstanceOf(RoutersInterface::class, $routers->addRouter('/kovey/test', new Router('/kovey/test', 'PUT')));
        $this->assertInstanceOf(RouterInterface::class, $routers->getRouter('/kovey/test', 'put'));
        $this->assertInstanceOf(RouterInterface::class, $routers->getRouter('/kovey/test', 'get'));
        $routers->disableDefault();
        $this->assertEquals(null, $routers->getRouter('/kovey/test', 'get'));
    }

    public function testDELETERouter()
    {
        $routers = new Routers();
        $this->assertInstanceOf(RoutersInterface::class, $routers->addRouter('/kovey/test', new Router('/kovey/test', 'DELETE')));
        $this->assertInstanceOf(RouterInterface::class, $routers->getRouter('/kovey/test', 'delete'));
        $this->assertInstanceOf(RouterInterface::class, $routers->getRouter('/kovey/test', 'get'));
        $routers->disableDefault();
        $this->assertEquals(null, $routers->getRouter('/kovey/test', 'get'));
    }
}
