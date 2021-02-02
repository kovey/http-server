<?php
/**
 * @description global router interface
 *
 * @package     Router
 *
 * @time        2019-10-20 00:27:28
 *
 * @author      kovey
 */
namespace Kovey\Web\App\Http\Router;

use Kovey\Web\App\Application;
use Kovey\Container\Event\Router as ER;

class Route
{
    /**
     * @description app object
     *
     * @var Application
     */
    private static Application $app;

    /**
     * @description set app
     *
     * @param Application $app
     *
     * @return void
     */
    public static function setApp(Application $app) : void
    {
        self::$app = $app;
    }

    /**
     * @description set get router
     *
     * @param string $uri
     *
     * @param callable $fun
     *
     * @return Router
     */
    public static function get(string $uri, array | callable | string $fun = '') : RouterInterface
    {
        $router = new Router($uri, ER::ROUTER_METHOD_GET, $fun);
        self::$app->registerRouter($uri, $router);
        return $router;
    }

    /**
     * @description 设置POST路由
     *
     * @param string $uri
     *
     * @param callable $fun
     *
     * @return Router
     */
    public static function post(string $uri, array | callable | string $fun = '') : RouterInterface
    {
        $router = new Router($uri, ER::ROUTER_METHOD_POST, $fun);
        self::$app->registerRouter($uri, $router);
        return $router;
    }

    /**
     * @description 设置PUT路由
     *
     * @param string $uri
     *
     * @param callable $fun
     *
     * @return Router
     */
    public static function put(string $uri, array | callable | string $fun = '') : RouterInterface
    {
        $router = new Router($uri, ER::ROUTER_METHOD_PUT, $fun);
        self::$app->registerRouter($uri, $router);
        return $router;
    }

    /**
     * @description 设置DELETE路由
     *
     * @param string $uri
     *
     * @param callable $fun
     *
     * @return Router
     */
    public static function delete(string $uri, array | callable | string $fun = '') : RouterInterface
    {
        $router = new Router($uri, ER::ROUTER_METHOD_DELETE, $fun);
        self::$app->registerRouter($uri, $router);
        return $router;
    }
}
