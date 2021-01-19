<?php
/**
 *
 * @description 路由对象
 *
 * @package     Router
 *
 * @time        2019-10-19 21:34:55
 *
 * @author      kovey
 */
namespace Kovey\Web\App\Http\Router;

use Kovey\Pipeline\Middleware\MiddlewareInterface;

class Router implements RouterInterface
{
    /**
     * @description URI
     *
     * @var string
     */
    private string $uri;

    /**
     * @description 中间件
     *
     * @var Array
     */
    private Array $middlewares;

    /**
     * @description Action
     *
     * @var string
     */
    private string $action;

    /**
     * @description controller
     *
     * @var string
     */
    private string $controller;

    /**
     * @description 雷鸣路径
     *
     * @var string
     */
    private string $classPath;

    /**
     * @description 是否有效
     *
     * @var bool
     */
    private bool $isValid;

    /**
     * @description 雷命
     *
     * @var string
     */
    private string $className;

    /**
     * @description 页面路径
     *
     * @var string
     */
    private string $viewPath;

    /**
     * @description action 名称
     *
     * @var string
     */
    private string $actionName;

    /**
     * @description 回调
     *
     * @var callable | Array
     */
    private $callable;

    /**
     * @description 构造
     *
     * @param string $uri
     *
     * @param callable $fun
     *
     * @return Router
     */
    public function __construct(string $uri, array | callable | string $fun = '')
    {
        $this->uri = str_replace('//', '/', $uri);
        $this->middlewares = array();
        $this->classPath = '';
        $this->isValid = true;
        if (is_callable($fun)) {
            $this->callable = $fun;
            return;
        }

        if (!empty($fun)) {
            $info = explode('@', $fun);
            if (count($info) != 2) {
                $this->isValid = false;
                return;
            }

            $this->controller = $info[1];
            $this->action = $info[0];
        } else {
            $this->parseRoute();
            if (!$this->isValid) {
                return;
            }
        }

        $this->callable = null;

        $this->className = trim(str_replace('/', '\\', $this->classPath) . '\\' . ucfirst($this->controller) . 'Controller', '\\');
        $this->viewPath = strtolower($this->classPath) . '/' . strtolower($this->controller) . '/' . strtolower($this->action);
        $this->classPath = $this->classPath . '/' . ucfirst($this->controller) . '.php';
        $this->actionName = $this->action . 'Action';
    }

    /**
     * @description 路由解析
     *
     * @return void
     */
    private function parseRoute() : void
    {
        if ($this->uri === '/') {
            $this->controller = 'index';
            $this->action = 'index';
            return;
        }

        if (!$this->isUri($this->uri)) {
            $this->isValid = false;
            return;
        }

        $info = explode('/', $this->uri);
        $count = count($info);
        if ($count < 2) {
            $this->controller = 'index';
            $this->action = 'index';
            return;
        }

        if ($count == 2) {
            if (empty($info[1])) {
                $this->controller = 'index';
            } else {
                $this->controller = $info[1];
            }

            $this->action = 'index';

            return;
        }

        if ($count == 3) {
            $this->controller = $info[1];
            $this->action = $info[2];
            return;
        }

        $this->classPath = '/' . ucfirst($info[1]);
        $this->controller = $info[2];
        $this->action = $info[3];
    }

    /**
     * @description 获取action
     *
     * @return string
     */
    public function getAction() : string
    {
        return $this->action;
    }

    /**
     * @description 获取控制器
     *
     * @return string
     */
    public function getController() : string
    {
        return $this->controller;
    }

    /**
     * @description 获取类路径
     *
     * @return string
     */
    public function getClassPath() : string
    {
        return $this->classPath;
    }

    /**
     * @description 添加中间件
     *
     * @param MiddlewareInterface $middleware
     *
     * @return Router
     */
    public function addMiddleware(MiddlewareInterface $middleware) : RouterInterface
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * @description 获取中间件
     *
     * @return Array
     */
    public function getMiddlewares() : Array
    {
        return $this->middlewares;
    }

    /**
     * @description URI是否合法
     *
     * @param string $uri
     *
     * @return bool
     */
    private function isUri($uri) : bool
    {
        return (bool)preg_match('/^\/[a-zA-Z]+(\/[a-zA-Z][a-zA-Z0-9]+){0,3}(\/.+){0,1}$/', $uri);
    }

    /**
     * @description 是否有效
     *
     * @return bool
     */
    public function isValid() : bool
    {
        return $this->isValid;
    }

    /**
     * @description 获取类名
     *
     * @return string
     */
    public function getClassName() : string
    {
        return $this->className;
    }

    /**
     * @description 获取action名称
     *
     * @return string
     */
    public function getActionName() : string
    {
        return $this->actionName;
    }

    /**
     * @description 获取页面路径
     *
     * @return string
     */
    public function getViewPath() : string
    {
        return $this->viewPath;
    }

    /**
     * @description 获取回调
     *
     * @return callable
     */
    public function getCallable() : callable | Array | null
    {
        return $this->callable;
    }

    /**
     * @description 获取URI
     *
     * @return string
     */
    public function getUri() : string
    {
        return $this->uri;
    }
}
