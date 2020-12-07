<?php
/**
 *
 * @description 管道
 *
 * @package     PipelineInterface
 *
 * @time        2019-10-20 17:21:08
 *
 * @author      kovey
 */
namespace Kovey\Web\App\Http\Pipeline;

use Kovey\Library\Container\ContainerInterface;
use Kovey\Web\App\Http\Request\RequestInterface;
use Kovey\Web\App\Http\Response\ResponseInterface;

interface PipelineInterface
{
    /**
     * @description 构造
     *
     * @param ContainerInterface $container
     *
     * @return Pipeline
     */
    public function __construct(ContainerInterface $container);

    /**
     * @description 设置请求对象和相应对象
     *
     * @param RequestInterface $request
     *
     * @param ResponseInterface $response
     *
     * @param string $traceId
     *
     * @return PipelineInterface
     */
    public function send(RequestInterface $request, ResponseInterface $response, string $traceId) : PipelineInterface;

    /**
     * @description 设置中间件
     *
     * @param Array $middlewares
     *
     * @return PipelineInterface
     */
    public function through(Array $middlewares) : PipelineInterface;

    /**
     * @description 设置方法
     *
     * @param string $method
     *
     * @return PipelineInterface
     */
    public function via(string $method) : PipelineInterface;

    /**
     * @description 处理函数
     *
     * @param callable $description
     *
     * @return mixed
     */
    public function then(callable $destination);
}
