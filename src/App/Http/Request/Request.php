<?php
/**
 *
 * @description Request
 *
 * @package     App\Http\Request
 *
 * @time        Tue Sep 24 08:58:22 2019
 *
 * @author      kovey
 */
namespace Kovey\Web\App\Http\Request;

use Kovey\Library\Util\Json;
use Kovey\Web\App\Http\Session\SessionInterface;

class Request implements RequestInterface
{
    /**
     * @description request object
     *
     * @var Swoole\Http\Request
     */
    private \Swoole\Http\Request $req;

    /**
     * @description server info
     *
     * @var Array
     */
    private Array $server;

    /**
     * @description controller
     *
     * @var string
     */
    private string $controller;

    /**
     * @description ACTION
     *
     * @var string
     */
    private string $action;

    /**
     * @description request params
     *
     * @var Array
     */
    private Array $params;

    /**
     * @description post params
     *
     * @var Array
     */
    private Array $post = array();

    /**
     * @description get params
     *
     * @var Array
     */
    private Array $get = array();

    /**
     * @description put params
     *
     * @var Array
     */
    private Array $put = array();

    /**
     * @description delete params
     *
     * @var Array
     */
    private Array $delete = array();

    /**
     * @description session
     *
     * @var SessionInterface
     */
    private SessionInterface $session;

    /**
     * @description context
     *
     * @var Context
     */
    private Context $context;

    /**
     * @description construct
     *
     * @param Swoole\Http\Request $request
     * 
     * @return Request
     */
    public function __construct(\Swoole\Http\Request $request)
    {
        $this->context = new Context();
        $this->req = $request;
        $this->server = $this->req->server;
        $this->parseData();
        $this->setGlobal();
        $this->params = array();
        $this->processParams();
    }

    /**
     * @description 构造函数
     * 
     * @return void
     */
    private function processParams() : void
    {
        $info = explode('/', $this->getUri());
        $len = count($info);
        if ($len < 5) {
            return;
        }

        for ($i = 3; $i < $len;) {
            $this->params[$info[$i]] = $info[$i + 1];
            $i += 2;
        }
    }

    /**
     * @description data parse
     * 
     * @return void
     */
    private function parseData() : void
    {
        $cType = explode(';', $this->req->header['content-type'] ?? '')[0];
        $method = $this->getMethod();
        if ($cType === 'application/json') {
            $origin = $this->req->getContent();
            if (!empty($origin)) {
                $data = Json::decode($origin);
            } else {
                $data = array();
            }
            if ($method === 'get') {
                $this->get = $data;
                return;
            }

            if ($method === 'post') {
                $this->post = $data;
                return;
            }

            if ($method === 'put') {
                $this->put = $data;
                return;
            }

            if ($method === 'delete') {
                $this->delete = $data;
            }

            return;
        }

        if ($method === 'get') {
            $this->get = is_array($this->req->get) ? $this->req->get : array(); 
        } else if ($method === 'post') {
            $this->post = is_array($this->req->post) ? $this->req->post : array(); 
        } else if ($method === 'put') {
            $this->put = is_array($this->req->post) ? $this->req->post : array(); 
        } else if ($method === 'delete') {
            $this->delete = is_array($this->req->post) ? $this->req->post : array(); 
        }

    }

    /**
     * @description set global
     * 
     * @return void
     */
    private function setGlobal() : void
    {
        foreach ($this->req->header as $key => $value) {
            $key = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
            $this->server[$key] = $value;
        }
    }

    /**
     * @description is websocket
     *
     * @return bool
     */
    public function isWebSocket() : bool
    {
        return isset($this->req->header['upgrade']) && strtolower($this->req->header['upgrade']) == 'websocket';
    }

    /**
     * @description get client ip
     *
     * @return string
     */
    public function getClientIP() : string
    {
        if (isset($this->server['HTTP_X_REAL_IP']) && strcasecmp($this->server['HTTP_X_REAL_IP'], 'unknown')) {
            return $this->server['HTTP_X_REAL_IP'];
        }
        if (isset($this->server['HTTP_CLIENT_IP']) && strcasecmp($this->server['HTTP_CLIENT_IP'], 'unknown')) {
            return $this->server['HTTP_CLIENT_IP'];
        }
        if (isset($this->server['HTTP_X_FORWARDED_FOR']) && strcasecmp($this->server['HTTP_X_FORWARDED_FOR'], 'unknown')) {
            return $this->server['HTTP_X_FORWARDED_FOR'];
        }
        if (isset($this->server['REMOTE_ADDR'])) {
            return $this->server['REMOTE_ADDR'];
        }

        if (isset($this->server['remote_addr'])) {
            return $this->server['remote_addr'];
        }

        return '';
    }

    /**
     * @description get brower
     *
     * @return string
     */
    public function getBrowser() : string
    {
        $sys = $this->server['HTTP_USER_AGENT'];
        if (stripos($sys, "Firefox/") > 0) {
            preg_match("/Firefox\/([^;)]+)+/i", $sys, $b);
            $exp[0] = "Firefox";
            $exp[1] = $b[1];
        } else if (stripos($sys, "Maxthon") > 0) {
            preg_match("/Maxthon\/([\d\.]+)/", $sys, $aoyou);
            $exp[0] = "傲游";
            $exp[1] = $aoyou[1];
        } else if (stripos($sys, "MSIE") > 0) {
            preg_match("/MSIE\s+([^;)]+)+/i", $sys, $ie);
            $exp[0] = "IE";
            $exp[1] = $ie[1];
        } else if (stripos($sys, "OPR") > 0) {
            preg_match("/OPR\/([\d\.]+)/", $sys, $opera);
            $exp[0] = "Opera";
            $exp[1] = $opera[1];
        } else if (stripos($sys, "Edge") > 0) {
            preg_match("/Edge\/([\d\.]+)/", $sys, $Edge);
            $exp[0] = "Edge";
            $exp[1] = $Edge[1];
        } else if (stripos($sys, "Chrome") > 0) {
            preg_match("/Chrome\/([\d\.]+)/", $sys, $google);
            $exp[0] = "Chrome";
            $exp[1] = $google[1];
        } else if (stripos($sys, 'rv:') > 0 && stripos($sys, 'Gecko') > 0) {
            preg_match("/rv:([\d\.]+)/", $sys, $IE);
            $exp[0] = "IE";
            $exp[1] = $IE[1];
        } else {
            $exp[0] = "Unkown";
            $exp[1] = "";
        }

        return $exp[0] . '(' . $exp[1] . ')';
    }
    
    /**
     * @description get client os
     *
     * @return string
     */
    public function getOS() : string
    {
        $agent = $this->server['HTTP_USER_AGENT'] ?? '';
        if (empty($agent)) {
            return '';
        }

        if (preg_match('/win/i', $agent) && strpos($agent, '95')) {
            $os = 'Windows 95';
        } else if (preg_match('/win 9x/i', $agent) && strpos($agent, '4.90')) {
            $os = 'Windows ME';
        } else if (preg_match('/win/i', $agent) && preg_match('/98/i', $agent)) {
            $os = 'Windows 98';
        } else if (preg_match('/win/i', $agent) && preg_match('/nt 6.0/i', $agent)) {
            $os = 'Windows Vista';
        } else if (preg_match('/win/i', $agent) && preg_match('/nt 6.1/i', $agent)) {
            $os = 'Windows 7';
        } else if (preg_match('/win/i', $agent) && preg_match('/nt 6.2/i', $agent)) {
            $os = 'Windows 8';
        } else if (preg_match('/win/i', $agent) && preg_match('/nt 10.0/i', $agent)) {
            $os = 'Windows 10';
        } else if (preg_match('/win/i', $agent) && preg_match('/nt 5.1/i', $agent)) {
            $os = 'Windows XP';
        } else if (preg_match('/win/i', $agent) && preg_match('/nt 5/i', $agent)) {
            $os = 'Windows 2000';
        } else if (preg_match('/win/i', $agent) && preg_match('/nt/i', $agent)) {
            $os = 'Windows NT';
        } else if (preg_match('/win/i', $agent) && preg_match('/32/i', $agent)) {
            $os = 'Windows 32';
        } else if (preg_match('/linux/i', $agent)) {
            $os = 'Linux';
        } else if (preg_match('/unix/i', $agent)) {
            $os = 'Unix';
        } else if (preg_match('/sun/i', $agent) && preg_match('/os/i', $agent)) {
            $os = 'SunOS';
        } else if (preg_match('/ibm/i', $agent) && preg_match('/os/i', $agent)) {
            $os = 'IBM OS/2';
        } else if (preg_match('/Macintosh/i', $agent)
            || (preg_match('/Mac/i', $agent) && preg_match('/OS X/i', $agent))
        ) {
            $os = 'Macintosh';
        } else if (preg_match('/PowerPC/i', $agent)) {
            $os = 'PowerPC';
        } else if (preg_match('/AIX/i', $agent)) {
            $os = 'AIX';
        } else if (preg_match('/HPUX/i', $agent)) {
            $os = 'HPUX';
        } else if (preg_match('/NetBSD/i', $agent)) {
            $os = 'NetBSD';
        } else if (preg_match('/BSD/i', $agent)) {
            $os = 'BSD';
        } else if (preg_match('/OSF1/i', $agent)) {
            $os = 'OSF1';
        } else if (preg_match('/IRIX/i', $agent)) {
            $os = 'IRIX';
        } else if (preg_match('/FreeBSD/i', $agent)) {
            $os = 'FreeBSD';
        } else if (preg_match('/teleport/i', $agent)) {
            $os = 'teleport';
        } else if (preg_match('/flashget/i', $agent)) {
            $os = 'flashget';
        } else if (preg_match('/webzip/i', $agent)) {
            $os = 'webzip';
        } else if (preg_match('/offline/i', $agent)) {
            $os = 'offline';
        } else {
            $os = 'Unknown';
        }

        return $os;
    }

    /**
     * @description get post data
     *
     * @param string $name
     *
     * @param mixed $default
     *
     * @return mixed
     */
    public function getPost(string $name = '', $default = '') : mixed
    {
        if (empty($name)) {
            return $this->post;
        }

        return $this->post[$name] ?? $default;
    }

    /**
     * @description get query data
     *
     * @param string $name
     *
     * @param string $default
     *
     * @return mixed
     */
    public function getQuery(string $name = '', $default = '') : mixed
    {
        if (empty($name)) {
            return $this->get;
        }

        return $this->get[$name] ?? $default;
    }

    /**
     * @description get put data
     *
     * @param string $name
     *
     * @param string $default
     *
     * @return mixed
     */
    public function getPut(string $name = '', $default = '') : mixed
    {
        if (empty($name)) {
            return $this->put;
        }

        return $this->put[$name] ?? $default;
    }

    /**
     * @description get delete data
     *
     * @param string $name
     *
     * @param string $default
     *
     * @return mixed
     */
    public function getDelete(string $name = '', $default = '') : mixed
    {
        if (empty($name)) {
            return $this->delete;
        }

        return $this->delete[$name] ?? $default;
    }

    /**
     * @description get request method
     *
     * @return string
     */
    public function getMethod() : string
    {
        return strtolower($this->server['request_method']);
    }

    /**
     * @description get uri
     *
     * @return string
     */
    public function getUri() : string
    {
        return $this->server['request_uri'] ?? '/';
    }

    /**
     * @description get param
     *
     * @param string $key
     *
     * @return string
     */
    public function getParam(string $key) : string
    {
        return $this->params[$key] ?? '';
    }

    /**
     * @description get base url
     *
     * @return string
     */
    public function getBaseUrl() : string
    {
        return $this->server['HTTP_HOST'] ?? '';
    }

    /**
     * @description set controller
     *
     * @param string $controller
     * 
     * @return RequestInterface
     */
    public function setController(string $controller) : RequestInterface
    {
        $this->controller = $controller;
        return $this;
    }

    /**
     * @description set action
     *
     * @param string $action
     * 
     * @return Request
     */
    public function setAction(string $action) : RequestInterface
    {
        $this->action = $action;
        return $this;
    }

    /**
     * @description get action
     * 
     * @return string
     */
    public function getAction() : string
    {
        return $this->action;
    }

    /**
     * @description get controller
     * 
     * @return string
     */
    public function getController() : string
    {
        return $this->controller;
    }

    /**
     * @description get php content
     * 
     * @return string
     */
    public function getPhpinput() : string
    {
        return $this->req->getContent();
    }

    /**
     * @description get cookie
     * 
     * @return Array
     */
    public function getCookie() : Array
    {
        return empty($this->req->cookie) ? array() : $this->req->cookie;
    }

    /**
     * @description get header
     *
     * @param string $name
     * 
     * @return string
     */
    public function getHeader($name) : string
    {
        return $this->req->header[strtolower($name)] ?? '';
    }

    /**
     * @description set session
     *
     * @param SessionInterface $session
     * 
     * @return RequestInterface
     */
    public function setSession(SessionInterface $session) : RequestInterface
    {
        $this->session = $session;
        return $this;
    }

    /**
     * @description get session
     * 
     * @return SessionInterface
     */
    public function getSession() : SessionInterface
    {
        return $this->session;
    }

    /**
     * @description get files uploaded from client
     *
     * @return array
     */
    public function getFiles() : Array
    {
        return $this->req->files;
    }

    /**
     * @description process cors
     *
     * @return RequestInterface
     */
    public function processCors() : RequestInterface
    {
        switch (strtolower($this->getMethod())) {
            case 'get':
                array_walk($this->get, function (&$row) {
                    if (is_array($row)) {
                        return;
                    }
                    $row = htmlspecialchars($row);
                });
                break;
            case 'post':
                array_walk($this->post, function (&$row) {
                    if (is_array($row)) {
                        return;
                    }
                    $row = htmlspecialchars($row);
                });
                break;
            case 'put':
                array_walk($this->put, function (&$row) {
                    if (is_array($row)) {
                        return;
                    }
                    $row = htmlspecialchars($row);
                });
                break;
            case 'delete':
                array_walk($this->delete, function (&$row) {
                    if (is_array($row)) {
                        return;
                    }
                    $row = htmlspecialchars($row);
                });
                break;
            default:
        }

        return $this;
    }
    
    /**
     * @description content
     *
     * @return Context
     */
    public function getContext() : Context
    {
        return $this->context;
    }
}
