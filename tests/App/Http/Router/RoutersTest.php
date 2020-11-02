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
        $this->assertInstanceOf(RoutersInterface::class, $routers->get('/kovey/test', new Router('/kovey/test')));
        $this->assertInstanceOf(RouterInterface::class, $routers->getRouter('/kovey/test', 'get'));
        $this->assertInstanceOf(RouterInterface::class, $routers->getRouter('/kovey/test', 'post'));
        $routers->disableDefault();
        $this->assertEquals(null, $routers->getRouter('/kovey/test', 'post'));
    }

    public function testPOSTRouter()
    {
        $routers = new Routers();
        $this->assertInstanceOf(RoutersInterface::class, $routers->post('/kovey/test', new Router('/kovey/test')));
        $this->assertInstanceOf(RouterInterface::class, $routers->getRouter('/kovey/test', 'post'));
        $this->assertInstanceOf(RouterInterface::class, $routers->getRouter('/kovey/test', 'get'));
        $routers->disableDefault();
        $this->assertEquals(null, $routers->getRouter('/kovey/test', 'get'));
    }

    public function testPUTRouter()
    {
        $routers = new Routers();
        $this->assertInstanceOf(RoutersInterface::class, $routers->put('/kovey/test', new Router('/kovey/test')));
        $this->assertInstanceOf(RouterInterface::class, $routers->getRouter('/kovey/test', 'put'));
        $this->assertInstanceOf(RouterInterface::class, $routers->getRouter('/kovey/test', 'get'));
        $routers->disableDefault();
        $this->assertEquals(null, $routers->getRouter('/kovey/test', 'get'));
    }

    public function testDELETERouter()
    {
        $routers = new Routers();
        $this->assertInstanceOf(RoutersInterface::class, $routers->delete('/kovey/test', new Router('/kovey/test')));
        $this->assertInstanceOf(RouterInterface::class, $routers->getRouter('/kovey/test', 'delete'));
        $this->assertInstanceOf(RouterInterface::class, $routers->getRouter('/kovey/test', 'get'));
        $routers->disableDefault();
        $this->assertEquals(null, $routers->getRouter('/kovey/test', 'get'));
    }
}
