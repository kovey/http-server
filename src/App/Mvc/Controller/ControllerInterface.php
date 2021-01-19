<?php
/**
 *
 * @description controller interface
 *
 * @package     Controller
 *
 * @time        2019-10-17 23:51:21
 *
 * @author      kovey
 */
namespace Kovey\Web\App\Mvc\Controller;

use Kovey\Web\App\Http\Response\ResponseInterface;
use Kovey\Web\App\Http\Request\RequestInterface;
use Kovey\Web\App\Http\Plugin\PluginInterface;
use Kovey\Web\App\Mvc\View\ViewInterface;

interface  ControllerInterface
{
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
    public function __construct(RequestInterface $req, ResponseInterface $res, Array $plugins = array());

    /**
     * @description set view
     *
     * @param ViewInterface $view
     *
     * @return ControllerInterface
     */
    public function setView(ViewInterface $view) : ControllerInterface;

    /**
     * @description render view
     *
     * @return ControllerInterface
     */
    public function render() : ControllerInterface;

    /**
     * @description get response
     *
     * @return ResponseInterface
     */
    public function getResponse() : ResponseInterface;

    /**
     * @description get request
     *
     * @return RequestInterface
     */
    public function getRequest() : RequestInterface;

    /**
     * @description init plugins
     *
     * @return ControllerInterface
     */
    public function initPlugins(Array $plugins) : ControllerInterface;

    /**
     * @description get plugins
     *
     * @return Array
     */
    public function getPlugins() : Array;

    /**
     * @description redirect
     *
     * @return ControllerInterface
     */
    public function redirect($url) : ControllerInterface;

    /**
     * @description disable view
     *
     * @return ControllerInterface
     */
    public function disableView() : ControllerInterface;

    /**
     * @description view status
     *
     * @return bool
     */
    public function isViewDisabled() : bool;

    /**
     * @description plugins status
     *
     * @return bool
     */
    public function isPluginDisabled() : bool;

    /**
     * @description 禁用插件
     *
     * @return ControllerInterface
     */
    public function disablePlugin() : ControllerInterface;

    /**
     * @description set header
     *
     * @param string $key
     *
     * @param string $val
     *
     * @return ControllerInterface
     */
    public function setHeader(string $key, string $val) : ControllerInterface;
}
