<?php
/**
 *
 * @description 控制器类
 *
 * @package     App\Mvc
 *
 * @time        Tue Sep 24 08:56:12 2019
 *
 * @author      kovey
 */
namespace Kovey\Web\App\Mvc\Controller;

use Kovey\Web\App\Http\Response\ResponseInterface;
use Kovey\Web\App\Http\Request\RequestInterface;
use Kovey\Web\App\Http\Plugin\PluginInterface;
use Kovey\Web\App\Mvc\View\ViewInterface;

class Controller implements ControllerInterface
{
	/**
	 * @description 页面
	 *
	 * @var ViewInterface
	 */
	protected ViewInterface $view;

	/**
	 * @description 请求对象
	 *
	 * @var RequestInterface
	 */
	protected RequestInterface $req;

	/**
	 * @description 插件
	 *
	 * @var Array
	 */
	protected Array $plugins;

	/**
	 * @description 是否禁用VIEW
	 *
	 * @var bool
	 */
	protected bool $isViewDisabled;

	/**
	 * @description 是否禁用插件
	 *
	 * @var bool
	 */
	protected bool $isPluginDisabled;

	/**
	 * @description response
	 *
	 * @var ResponseInterface
	 */
	protected ResponseInterface $res;

	/**
	 * @description 构造函数
	 *
	 * @param RequestInterface $req
	 *
	 * @param ResponseInterface $res
	 *
	 * @param Array $plugins
	 *
	 * @return ControllerInterface
	 */
	final public function __construct(RequestInterface $req, ResponseInterface $res, Array $plugins = array())
	{
		$this->isViewDisabled = false;
		$this->isPluginDisabled = false;
		$this->req = $req;
		$this->res = $res;
		$this->plugins = array();
		$this->initPlugins($plugins);

		$this->init();
	}

	/**
	 * @description 设置VIEW
	 *
	 * @param ViewInterface $view
	 *
	 * @return null
	 */
	public function setView(ViewInterface $view)
	{
		$this->view = $view;
	}

	/**
	 * @description 初始化
	 *
	 * @return null
	 */
	protected function init()
	{}

	/**
	 * @description 渲染页面
	 *
	 * @return null
	 */
	public function render()
	{
		$this->view->render();
	}

	/**
	 * @description 获取响应对象
	 *
	 * @return ResponseInterface
	 */
	public function getResponse() : ResponseInterface
	{
		return $this->res;
	}

	/**
	 * @description 获取请求对象
	 *
	 * @return RequestInterface
	 */
	public function getRequest() : RequestInterface
	{
		return $this->req;
	}

	/**
	 * @description 初始化插件
	 *
	 * @return null
	 */
	public function initPlugins(Array $plugins)
	{
		foreach ($plugins as $plugin) {
			$pclass = '\\' . $plugin;
			$pg = new $pclass(); 
			if (!$pg instanceof PluginInterface) {
				continue;
			}

			$this->plugins[$plugin] = $pg;
		}
	}

	/**
	 * @description 获取插件
	 *
	 * @return Array
	 */
	public function getPlugins() : Array
	{
		return $this->plugins;
	}

	/**
	 * @description 页面跳转
	 *
	 * @return null
	 */
	public function redirect($url)
	{
		$this->getResponse()->redirect($url);
	}

	/**
	 * @description 禁用页面
	 *
	 * @return null
	 */
	public function disableView()
	{
		$this->isViewDisabled = true;
	}

	/**
	 * @description 页面是否禁用
	 *
	 * @return bool
	 */
	public function isViewDisabled() : bool
	{
		return $this->isViewDisabled;
	}

	/**
	 * @description 插件是否禁用
	 *
	 * @return bool
	 */
	public function isPluginDisabled() : bool
	{
		return $this->isPluginDisabled;
	}

	/**
	 * @description 禁用插件
	 *
	 * @return null
	 */
	public function disablePlugin()
	{
		$this->isPluginDisabled = true;
	}

	/**
	 * @description 设置头信息
	 *
	 * @return null
	 */
	public function setHeader(string $key, string $val)
	{
		$this->getResponse()->setHeader($key, $val);
	}
}
