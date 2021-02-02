<?php
/**
 *
 * @description 路由接口
 *
 * @package     
 *
 * @time        2019-10-19 22:09:09
 *
 * @author      kovey
 */
namespace Kovey\Web\App\Http\Router;

use Kovey\Pipeline\Middleware\MiddlewareInterface;

interface RouterInterface
{
    /**
     * @description 构造
     *
     * @param string $uri
     *
     * @param string $method
     *
     * @param callable $fun
     *
     * @return RouterInterface
     */
    public function __construct(string $uri, string $method, array | callable | string $fun = '');

    /**
     * @description 获取action
     *
     * @return string
     */
    public function getAction() : string;

    /**
     * @description 获取控制器
     *
     * @return string
     */
    public function getController() : string;

    /**
     * @description 获取类路径
     *
     * @return string
     */
    public function getClassPath() : string;

    /**
     * @description 添加中间件
     *
     * @param MiddlewareInterface $middleware
     *
     * @return Router
     */
    public function addMiddleware(MiddlewareInterface $middleware) : RouterInterface;

    /**
     * @description 获取中间件
     *
     * @return Array
     */
    public function getMiddlewares() : Array;

    /**
     * @description 是否有效
     *
     * @return bool
     */
    public function isValid() : bool;

    /**
     * @description 获取类名
     *
     * @return string
     */
    public function getClassName() : string;

    /**
     * @description 获取action名称
     *
     * @return string
     */
    public function getActionName() : string;

    /**
     * @description 获取页面路径
     *
     * @return string
     */
    public function getViewPath() : string;

    /**
     * @description 获取回调
     *
     * @return callable
     */
    public function getCallable() : callable | array | null;

    /**
     * @description 获取URI
     *
     * @return string
     */
    public function getUri() : string;

    public function getMethod() : string;
}    
