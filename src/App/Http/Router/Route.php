<?php
/**
 *
 * @description 全局路由接口
 *
 * @package     Router
 *
 * @time        2019-10-20 00:27:28
 *
 * @author      kovey
 */
namespace Kovey\Web\App\Http\Router;

use Kovey\Web\App\Application;

class Route
{
	/**
	 * @description 全局大对象
	 *
	 * @var Application
	 */
	private static Application $app;

	/**
	 * @description 设置APP
	 *
	 * @param Application $app
	 *
	 * @return null
	 */
	public static function setApp(Application $app)
	{
		self::$app = $app;
	}

	/**
	 * @description 设置GET路由
	 *
	 * @param string $uri
	 *
	 * @param callable $fun
	 *
	 * @return Router
	 */
	public static function get(string $uri, $fun = null) : RouterInterface
	{
		$router = new Router($uri, $fun);
		self::$app->registerGetRouter($uri, $router);
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
	public static function post(string $uri, $fun = null) : RouterInterface
	{
		$router = new Router($uri, $fun);
		self::$app->registerPostRouter($uri, $router);
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
	public static function put(string $uri, $fun = null) : RouterInterface
	{
		$router = new Router($uri, $fun);
		self::$app->registerPutRouter($uri, $router);
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
	public static function delete(string $uri, $fun = null) : RouterInterface
	{
		$router = new Router($uri, $fun);
		self::$app->registerDelRouter($uri, $router);
		return $router;
	}
}
