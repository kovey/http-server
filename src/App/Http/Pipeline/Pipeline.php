<?php
/**
 *
 * @description 中间件管道
 *
 * @package     Middleware
 *
 * @time        2019-10-19 12:34:45
 *
 * @author      kovey
 */
namespace Kovey\Web\App\Http\Pipeline;

use Kovey\Web\App\Http\Request\RequestInterface;
use Kovey\Web\App\Http\Response\ResponseInterface;
use Kovey\Library\Container\ContainerInterface;

class Pipeline implements PipelineInterface
{
    /**
     * @description 请求对象
     *
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @description 方法
     *
     * @var string
     */
    private string $method;

    /**
     * @description 容器
     *
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * @description 相应对象
     *
     * @var ResponseInterface
     */
    private ResponseInterface $response;

    /**
     * @description 构造
     *
     * @param ContainerInterface $container
     *
     * @return Pipeline
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @description 设置请求对象和相应对象
     *
     * @param RequestInterface $request
     *
     * @param ResponseInterface $response
     *
     * @return Pipeline
     */
    public function send(RequestInterface $request, ResponseInterface $response) : PipelineInterface
    {
        $this->request = $request;
        $this->response = $response;
        return $this;
    }

    /**
     * @description 设置中间件
     *
     * @param Array $middlewares
     *
     * @return Pipeline
     */
    public function through(Array $middlewares) : PipelineInterface
    {
        $this->middlewares = $middlewares;
        return $this;
    }

    /**
     * @description 设置方法
     *
     * @param string $method
     *
     * @return Pipeline
     */
    public function via(string $method) : PipelineInterface
    {
        $this->method = $method;
        return $this;
    }

    /**
     * @description 处理函数
     *
     * @param callable $description
     *
     * @return mixed
     */
    public function then(callable $destination)
    {
        $pipeline = array_reduce(
            array_reverse($this->middlewares), $this->carry(), $this->prepareDestination($destination)
        );

        return $pipeline($this->request, $this->response);
    }

    /**
     * @description 构造处理函数
     *
     * @return callable
     */
    protected function carry() : callable
    {
        return function ($stack, $pipe) {
            return function ($request, $response) use ($stack, $pipe) {
                if (is_callable($pipe)) {
                    return call_user_func($pipe, $request, $response, $stack);
                } elseif (! is_object($pipe)) {
                    list($name, $parameters) = $this->parsePipeString($pipe);

                    $pipe = $this->container->get($name);

                    $parameters = array_merge([$request, $response, $stack], $parameters);
                } else {
                    $parameters = [$request, $response, $stack];
                }

                return $pipe->{$this->method}(...$parameters);
            };
        };
    }

    /**
     * @description 构造下一个
     *
     * @param callable $description
     *
     * @return callable
     */
    protected function prepareDestination(callable $destination) : callable
    {
        return function ($request, $response) use ($destination) {
            return call_user_func($destination, $request, $response);
        };
    }

    /**
     * @description 解析参数
     *
     * @param string $pipe
     *
     * @return Array
     */
    protected function parsePipeString(string $pipe) : Array
    {
        list($name, $parameters) = array_pad(explode(':', $pipe, 2), 2, []);

        if (is_string($parameters)) {
            $parameters = explode(',', $parameters);
        }

        return [$name, $parameters];
    }
}
