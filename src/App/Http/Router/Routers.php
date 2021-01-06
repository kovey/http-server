<?php
/**
 *
 * @description 路由器
 *
 * @package     App\Http\Router
 *
 * @time        Tue Sep 24 08:56:49 2019
 *
 * @author      kovey
 */
namespace Kovey\Web\App\Http\Router;

class Routers implements RoutersInterface
{
    /**
     * @description GET路由
     *
     * @var Array
     */
    private Array $getRoutes;

    /**
     * @description POST路由
     *
     * @var Array
     */
    private Array $postRoutes;

    /**
     * @description PUT路由
     *
     * @var Array
     */
    private Array $putRoutes;

    /**
     * @description DELETE路由
     *
     * @var Array
     */
    private Array $delRoutes;

    /**
     * @description 默认路由
     *
     * @var Array
     */
    private Array $defaults;

    /**
     * @description 是否禁用默认路由
     *
     * @var bool
     */
    private bool $isDisableDefault = false;

    /**
     * @description 构造函数
     *
     * @return Routers
     */
    public function __construct()
    {
        $this->getRoutes = array();
        $this->postRoutes = array();
        $this->putRoutes = array();
        $this->delRoutes = array();
        $this->defaults = array();
    }

    /**
     * @description 获取路由
     *
     * @param string $uri
     *
     * @param string $method
     *
     * @return RoutersInterface
     */
    public function getRouter(string $uri, string $method) : ? RouterInterface
    {
        $uri = str_replace(array('//', '\\'), array('/'), $uri);
        if ($uri !== '/') {
            if (!$this->isUri($uri)) {
                return null;
            }
        }

        if ($method === 'get') {
            return $this->getRoutes[$uri] ?? $this->defaultRoute($uri);
        }

        if ($method === 'post') {
            return $this->postRoutes[$uri] ?? $this->defaultRoute($uri);
        }

        if ($method === 'put') {
            return $this->putRoutes[$uri] ?? $this->defaultRoute($uri);
        }

        if ($method === 'delete') {
            return $this->delRoutes[$uri] ?? $this->defaultRoute($uri);
        }

        return null;
    }

    /**
     * @description uri是否合法
     *
     * @param string $uri
     *
     * @return bool
     */
    public function isUri(string $uri) : bool
    {
        return (bool)preg_match('/^\/[a-zA-Z]+(\/[a-zA-Z][a-zA-Z0-9]+){0,3}(\/.+){0,1}$/', $uri);
    }

    /**
     * @description 默认路由
     *
     * @param string $uri
     *
     * @return RouterInterface
     */
    public function defaultRoute(string $uri) : ? RouterInterface
    {
        if ($this->isDisableDefault) {
            return null;
        }

        $uri = str_replace(array('//', '\\'), array('/'), $uri);

        if (isset($this->defaults[$uri])) {
            return $this->defaults[$uri];
        }

        $router = new Router($uri);
        if (!$router->isValid()) {
            return null;
        }

        $this->defaults[$router->getUri()] = $router;
        return $router;
    }

    /**
     * @description 添加GET路由
     *
     * @param string $uri
     *
     * @param RouterInterface $router
     *
     * @return RoutersInterface
     */
    public function get(string $uri, RouterInterface $router) : RoutersInterface
    {
        if ($router->isValid()) {
            $this->getRoutes[$uri] = $router;
        }
        return $this;
    }

    /**
     * @description 添加POST路由
     *
     * @param string $uri
     *
     * @param RouterInterface $router
     *
     * @return RoutersInterface
     */
    public function post(string $uri, RouterInterface $router) : RoutersInterface
    {
        if ($router->isValid()) {
            $this->postRoutes[$uri] = $router;
        }
        return $this;
    }

    /**
     * @description 添加PUT路由
     *
     * @param string $uri
     *
     * @param RouterInterface $router
     *
     * @return RoutersInterface
     */
    public function put(string $uri, RouterInterface $router) : RoutersInterface
    {
        if ($router->isValid()) {
            $this->putRoutes[$uri] = $router;
        }
        return $this;
    }

    /**
     * @description 添加DELETE路由
     *
     * @param string $uri
     *
     * @param RouterInterface $router
     *
     * @return RoutersInterface
     */
    public function delete(string $uri, RouterInterface $router) : RoutersInterface
    {
        if ($router->isValid()) {
            $this->delRoutes[$uri] = $router;
        }
        return $this;
    }

    /**
     * @description 禁用默认路由
     *
     * @return null
     */
    public function disableDefault()
    {
        $this->isDisableDefault = true;
    }
}
