<?php
/**
 *
 * @description controller
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
     * @description view
     *
     * @var ViewInterface
     */
    protected ViewInterface $view;

    /**
     * @description request
     *
     * @var RequestInterface
     */
    protected RequestInterface $req;

    /**
     * @description plugins
     *
     * @var Array
     */
    protected Array $plugins;

    /**
     * @description disable view
     *
     * @var bool
     */
    protected bool $isViewDisabled;

    /**
     * @description disable plugins
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
     * @description construct
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
     * @description set view
     *
     * @param ViewInterface $view
     *
     * @return ControllerInterface
     */
    public function setView(ViewInterface $view) : ControllerInterface
    {
        $this->view = $view;
        return $this;
    }

    /**
     * @description 初始化
     *
     * @return void
     */
    protected function init() : void
    {}

    /**
     * @description render view
     *
     * @return ControllerInterface
     */
    public function render() : ControllerInterface
    {
        $this->view->render();
        return $this;
    }

    /**
     * @description get response
     *
     * @return ResponseInterface
     */
    public function getResponse() : ResponseInterface
    {
        return $this->res;
    }

    /**
     * @description get request
     *
     * @return RequestInterface
     */
    public function getRequest() : RequestInterface
    {
        return $this->req;
    }

    /**
     * @description init plugins
     *
     * @return ControllerInterface
     */
    public function initPlugins(Array $plugins) : ControllerInterface
    {
        foreach ($plugins as $plugin) {
            $pclass = '\\' . $plugin;
            $pg = new $pclass(); 
            if (!$pg instanceof PluginInterface) {
                continue;
            }

            $this->plugins[$plugin] = $pg;
        }

        return $this;
    }

    /**
     * @description get plugin
     *
     * @return Array
     */
    public function getPlugins() : Array
    {
        return $this->plugins;
    }

    /**
     * @description redirect
     *
     * @return ControllerInterface
     */
    public function redirect($url) : ControllerInterface
    {
        $this->getResponse()->redirect($url);
        return $this;
    }

    /**
     * @description disable view
     *
     * @return ControllerInterface
     */
    public function disableView() : ControllerInterface
    {
        $this->isViewDisabled = true;
        return $this;
    }

    /**
     * @description view is disabled or enabled
     *
     * @return bool
     */
    public function isViewDisabled() : bool
    {
        return $this->isViewDisabled;
    }

    /**
     * @description plugin is disabled or enabled
     *
     * @return bool
     */
    public function isPluginDisabled() : bool
    {
        return $this->isPluginDisabled;
    }

    /**
     * @description disable plugin
     *
     * @return ControllerInterface
     */
    public function disablePlugin() : ControllerInterface
    {
        $this->isPluginDisabled = true;
        return $this;
    }

    /**
     * @description set header
     *
     * @return ControllerInterface
     */
    public function setHeader(string $key, string $val) : ControllerInterface
    {
        $this->getResponse()->setHeader($key, $val);
        return $this;
    }
}
