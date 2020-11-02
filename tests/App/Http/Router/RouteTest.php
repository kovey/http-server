<?php
/**
 * @description
 *
 * @package
 *
 * @author kovey
 *
 * @time 2020-10-30 16:21:03
 *
 */
namespace Kovey\Web\App\Http\Router;

use PHPUnit\Framework\TestCase;
use Kovey\Web\App\Application;

class RouteTest extends TestCase
{
    public static function setUpBeforeClass() : void
    {
        Route::setApp(Application::getInstance());
    }

    public function testGet()
    {
        $this->assertInstanceOf(RouterInterface::class, Route::get('/kovey/test'));
    }

    public function testPost()
    {
        $this->assertInstanceOf(RouterInterface::class, Route::post('/kovey/test'));
    }

    public function testPut()
    {
        $this->assertInstanceOf(RouterInterface::class, Route::put('/kovey/test'));
    }

    public function testDelete()
    {
        $this->assertInstanceOf(RouterInterface::class, Route::delete('/kovey/test'));
    }
}
