<?php
/**
 *
 * @description 中间件接口
 *
 * @package     Middleware
 *
 * @time        2019-10-19 12:32:28
 *
 * @author      kovey
 */
namespace Kovey\Web\Middleware;

use Kovey\Web\App\Http\Request\RequestInterface;
use Kovey\Web\App\Http\Response\ResponseInterface;

interface MiddlewareInterface
{
    /**
     * @description 中间件实现方法
     *
     * @param RequestInterface $req
     *
     * @param ResponseInterface $res
     *
     * @param callable $next
     *
     * @return mixed
     */
    public function handle(RequestInterface $req, ResponseInterface $res, callable $next);
}
