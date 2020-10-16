<?php
/**
 *
 * @description  View Interface
 *
 * @package     
 *
 * @time        Mon Sep 30 09:54:42 2019
 *
 * @author      kovey
 */
namespace Kovey\Web\App\Mvc\View;
use Kovey\Web\App\Http\Response\ResponseInterface;

interface ViewInterface
{
	/**
	 * @description 构造
	 *
	 * @param ResponseInterface $res
	 *
	 * @param string $template
	 *
	 * @return ViewInterface
	 */
	public function __construct(ResponseInterface $res, string $template);

	/**
	 * @description 设置模板
	 *
	 * @param string $template
	 *
	 * @return null
	 */
	public function setTemplate($template);

	/**
	 * @description 页面渲染
	 *
	 * @return null
	 */
	public function render();

	/**
	 * @description 获取响应对象
	 *
	 * @return ResponseInterface
	 */
	public function getResponse() : ResponseInterface;
}
