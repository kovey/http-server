<?php
/**
 *
 * @description 路由接口
 *
 * @package     Router
 *
 * @time        2019-10-17 23:27:59
 *
 * @author      kovey
 */
namespace Kovey\Web\App\Http\Router;

interface RoutersInterface
{
	/**
	 * @description 获取路由
	 *
	 * @param string $uri
	 *
	 * @param string $method
	 *
	 * @return RoutersInterface
	 */
	public function getRouter(string $uri, string $method) : ? RouterInterface;

	/**
	 * @description uri是否合法
	 *
	 * @param string $uri
	 *
	 * @return bool
	 */
	public function isUri(string $uri) : bool;

	/**
	 * @description 默认路由
	 *
	 * @param string $uri
	 *
	 * @return RouterInterface
	 */
	public function defaultRoute(string $uri) : ? RouterInterface;

	/**
	 * @description 添加GET路由
	 *
	 * @param string $uri
	 *
	 * @param RouterInterface $router
	 *
	 * @return RoutersInterface
	 */
	public function get(string $uri, RouterInterface $router) : RoutersInterface;

	/**
	 * @description 添加POST路由
	 *
	 * @param string $uri
	 *
	 * @param RouterInterface $router
	 *
	 * @return RoutersInterface
	 */
	public function post(string $uri, RouterInterface $router) : RoutersInterface;

	/**
	 * @description 添加PUT路由
	 *
	 * @param string $uri
	 *
	 * @param RouterInterface $router
	 *
	 * @return RoutersInterface
	 */
	public function put(string $uri, RouterInterface $router) : RoutersInterface;

	/**
	 * @description 添加DELETE路由
	 *
	 * @param string $uri
	 *
	 * @param RouterInterface $router
	 *
	 * @return RoutersInterface
	 */
	public function delete(string $uri, RouterInterface $router) : RoutersInterface;

	/**
	 * @description 禁用默认路由
	 *
	 * @return null
	 */
	public function disableDefault();
}
