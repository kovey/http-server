<?php
/**
 *
 * @description 插件接口
 *
 * @package     App\Http\Plugin
 *
 * @time        Tue Sep 24 08:59:02 2019
 *
 * @author      kovey
 */
namespace Kovey\Web\App\Http\Plugin;
use Kovey\Web\App\Http\Request\RequestInterface;
use Kovey\Web\App\Http\Response\ResponseInterface;

interface PluginInterface
{
	/**
	 * @description 事件循环节将关闭
	 *
	 * @param RequestInterface $request
	 *
	 * @param ResponseInterface $response
	 *
	 * @return mixed
	 */
	public function loopShutdown(RequestInterface $request, ResponseInterface $response);
}
