<?php
/**
 * @description router
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
     * @description routers
     *
     * @var Array
     */
    private Array $routers;

    /**
     * @description default routers
     *
     * @var Array
     */
    private Array $defaults;

    /**
     * @description disable default router ?
     *
     * @var bool
     */
    private bool $isDisableDefault = false;

    /**
     * @description constructor
     *
     * @return Routers
     */
    public function __construct()
    {
        $this->routers = array();
        $this->defaults = array();
    }

    /**
     * @description get router
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

        return $this->routers[strtolower($method)][$uri] ?? $this->defaultRoute($uri);
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
     * @description add router
     *
     * @param string $uri
     *
     * @param RouterInterface $router
     *
     * @return RoutersInterface
     */
    public function addRouter(string $uri, RouterInterface $router) : RoutersInterface
    {
        if (!$router->isValid()) {
            return $this;
        }

        $this->routers[$router->getMethod()] ??= array();
        $this->routers[$router->getMethod()][$uri] = $router;

        return $this;
    }

    /**
     * @description disable default router
     *
     * @return RoutersInterface
     */
    public function disableDefault() : RoutersInterface
    {
        $this->isDisableDefault = true;
        return $this;
    }
}
