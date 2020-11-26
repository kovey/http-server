<?php
/**
 *
 * @description 请求接口
 *
 * @package     Request
 *
 * @time        2019-10-17 23:41:10
 *
 * @author      kovey
 */
namespace Kovey\Web\App\Http\Request;

use Kovey\Web\App\Http\Session\SessionInterface;

interface RequestInterface
{
    /**
     * @description 构造函数
     *
     * @param Swoole\Http\Request $request
     * 
     * @return Request
     */
    public function __construct(\Swoole\Http\Request $request);

    /**
     * @description 判断是否是WEBSocket
     *
     * @return bool
     */
    public function isWebSocket() : bool;

    /**
     * @description 获取客户端IP
     *
     * @return string
     */
    public function getClientIP() : string;

    /**
     * @description 获取浏览器信息
     *
     * @return string
     */
    public function getBrowser() : string;
    
    /**
     * @description 获取客户端系统信息
     *
     * @return string
     */
    public function getOS() : string;

    /**
     * @description 获取POST请求数据
     *
     * @param string $name
     *
     * @param mixed $default
     *
     * @return string | Array
     */
    public function getPost(string $name = '', $default = '') : Array | string;

    /**
     * @description 获取GET请求数据
     *
     * @param string $name
     *
     * @param mixed $default
     *
     * @return string | Array
     */
    public function getQuery(string $name = '', $default = '') : Array | string;

    /**
     * @description 获取PUT请求数据
     *
     * @param string $name
     *
     * @param mixed $default
     *
     * @return string
     */
    public function getPut(string $name = '', $default = '') : Array | string;

    /**
     * @description 获取DELETE请求数据
     *
     * @param string $name
     *
     * @param mixed $default
     *
     * @return string
     */
    public function getDelete(string $name = '', $default = '') : Array | string;

    /**
     * @description 获取METHOD
     *
     * @return string
     */
    public function getMethod() : string;

    /**
     * @description 获取URI
     *
     * @return string
     */
    public function getUri() : string;

    /**
     * @description 获取参数
     *
     * @param string $key
     *
     * @return string
     */
    public function getParam(string $key) : string;

    /**
     * @description 获取baseurl
     *
     * @return string
     */
    public function getBaseUrl() : string;

    /**
     * @description 设置控制器
     *
     * @param string $controller
     * 
     * @return Request
     */
    public function setController(string $controller) : RequestInterface;

    /**
     * @description 设置Action
     *
     * @param string $action
     * 
     * @return Request
     */
    public function setAction(string $action) : RequestInterface;

    /**
     * @description 获取ACTION
     * 
     * @return string
     */
    public function getAction() : string;

    /**
     * @description 获取控制器
     * 
     * @return string
     */
    public function getController() : string;

    /**
     * @description 获取原始数据
     * 
     * @return string
     */
    public function getPhpinput() : string;

    /**
     * @description 获取cookie
     * 
     * @return Array
     */
    public function getCookie() : Array;

    /**
     * @description 获取头信息
     *
     * @param string $name
     * 
     * @return string
     */
    public function getHeader(string $name) : string;

    /**
     * @description 设置Session
     *
     * @param SessionInterface $session
     * 
     * @return RequestInterface
     */
    public function setSession(SessionInterface $session) : RequestInterface;

    /**
     * @description 获取Sesstion
     * 
     * @return SessionInterface
     */
    public function getSession() : SessionInterface;

    /**
     * @description 获取文件
     *
     * @return Array
     */
    public function getFiles() : Array;

    /**
     * @description 跨域攻击处理
     *
     * @return RequestInterface
     */
    public function processCors() : RequestInterface;
}
