<?php
/**
 *
 * @description request interface
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
     * @description construct
     *
     * @param Swoole\Http\Request $request
     * 
     * @return Request
     */
    public function __construct(\Swoole\Http\Request $request);

    /**
     * @description is websocket
     *
     * @return bool
     */
    public function isWebSocket() : bool;

    /**
     * @description get client ip
     *
     * @return string
     */
    public function getClientIP() : string;

    /**
     * @description get brower
     *
     * @return string
     */
    public function getBrowser() : string;
    
    /**
     * @description get os
     *
     * @return string
     */
    public function getOS() : string;

    /**
     * @description get post data
     *
     * @param string $name
     *
     * @param mixed $default
     *
     * @return mixed
     */
    public function getPost(string $name = '', $default = '') : mixed;

    /**
     * @description get query data
     *
     * @param string $name
     *
     * @param mixed $default
     *
     * @return mixed
     */
    public function getQuery(string $name = '', $default = '') : mixed;

    /**
     * @description get put data
     *
     * @param string $name
     *
     * @param mixed $default
     *
     * @return mixed
     */
    public function getPut(string $name = '', $default = '') : mixed;

    /**
     * @description get delete
     *
     * @param string $name
     *
     * @param mixed $default
     *
     * @return mixed
     */
    public function getDelete(string $name = '', $default = '') : mixed;

    /**
     * @description get method
     *
     * @return string
     */
    public function getMethod() : string;

    /**
     * @description get uri
     *
     * @return string
     */
    public function getUri() : string;

    /**
     * @description get param
     *
     * @param string $key
     *
     * @return string
     */
    public function getParam(string $key) : string;

    /**
     * @description get base url
     *
     * @return string
     */
    public function getBaseUrl() : string;

    /**
     * @description set controller
     *
     * @param string $controller
     * 
     * @return RequestInterface
     */
    public function setController(string $controller) : RequestInterface;

    /**
     * @description set action
     *
     * @param string $action
     * 
     * @return RequestInterface
     */
    public function setAction(string $action) : RequestInterface;

    /**
     * @description get action
     * 
     * @return string
     */
    public function getAction() : string;

    /**
     * @description get controller
     * 
     * @return string
     */
    public function getController() : string;

    /**
     * @description get php input
     * 
     * @return string
     */
    public function getPhpinput() : string;

    /**
     * @description get cookie
     * 
     * @return Array
     */
    public function getCookie() : Array;

    /**
     * @description get header
     *
     * @param string $name
     * 
     * @return string
     */
    public function getHeader(string $name) : string;

    /**
     * @description set session
     *
     * @param SessionInterface $session
     * 
     * @return RequestInterface
     */
    public function setSession(SessionInterface $session) : RequestInterface;

    /**
     * @description get session
     * 
     * @return SessionInterface
     */
    public function getSession() : SessionInterface;

    /**
     * @description get upload files from client
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
