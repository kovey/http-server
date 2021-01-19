<?php
/**
 *
 * @description Response
 *
 * @package     App\Http\Response
 *
 * @time        Tue Sep 24 08:57:32 2019
 *
 * @author      kovey
 */
namespace Kovey\Web\App\Http\Response;

class Response implements ResponseInterface
{
    /**
     * @description HTTP协议
     *
     * @var string
     */
    private string $protocol = 'HTTP/1.1';

    /**
     * @description 状态码
     *
     * @var int
     */
    private int $status = 200;

    /**
     * @description 头信息
     *
     * @var Array
     */
    private Array $head;

    /**
     * @description cookie
     *
     * @var Array
     */
    private Array $cookie;

    /**
     * @description body
     *
     * @var string
     */
    private string $body = '';

    /**
     * @description construct
     *
     * @return Response
     */
    public function __construct()
    {
        $this->head = array();
        $this->cookie = array();
        $this->head['Server'] = 'kovey framework';
        $this->head['Connection'] = 'keep-alive';
        $this->head['Content-Type'] = 'text/html; charset=utf-8';
    }

    /**
     * @description set status
     *
     * @param int $code
     *
     * @return ResponseInterface
     */
    public function status(int $code) : ResponseInterface
    {
        $this->status = $code;
        return $this;
    }

    /**
     * @description redirect
     *
     * @param string $url
     *
     * @return ResponseInterface
     */
    public function redirect(string $url) : ResponseInterface
    {
        $this->status(302);
        $this->setHeader('Location', $url);
        return $this;
    }

    /**
     * @description set header
     *
     * @param string $key
     *
     * @param string $val
     *
     * @return ResponseInterface
     */
    public function setHeader(string $key, string $value) : ResponseInterface
    {
        $this->head[$key] = $value;
        return $this;
    }

    /**
     * @description set cookie
     *
     * @param string $name
     *
     * @param string $value
     *
     * @param string $expire
     *
     * @param string $path
     *
     * @param string $domain
     *
     * @param string $secure
     *
     * @param string $httponly
     *
     * @return Response
     */
    public function setCookie(
        string $name, ?string $value = null, ?string $expire = null, string $path = '/', ?string $domain = null, ?string $secure = null, ?string $httponly = null
    ) : ResponseInterface
    {
        if ($value == null) {
            $value = 'deleted';
        }
        $cookie = "$name=$value";
        if ($expire) {
            $cookie .= "; expires=" . date("D, d-M-Y H:i:s T", $expire);
        }

        if ($path) {
            $cookie .= "; path=$path";
        }
        if ($secure) {
            $cookie .= "; secure";
        }
        if ($domain) {
            $cookie .= "; domain=$domain";
        }
        if ($httponly) {
            $cookie .= '; httponly';
        }
        $this->cookie[] = $cookie;
        return $this;
    }

    /**
     * @description add headers
     *
     * @param Array $header
     *
     * @return ResponseInterface
     */
    public function addHeaders(array $header) : ResponseInterface
    {
        $this->head = array_merge($this->head, $header);
        return $this;
    }

    /**
     * @description get header
     *
     * @param bool $fastcgi
     *
     * @return string
     */
    public function getHeader(bool $fastcgi = false) : string
    {
        $out = '';
        if ($fastcgi) {
            $out .= 'Status: '.$this->status.' '.self::$HTTP_HEADERS[$this->http_status]."\r\n";
        } else {
            if (isset($this->head[0])) {
                $out .= $this->head[0]."\r\n";
                unset($this->head[0]);
            } else {
                $out = "HTTP/1.1 200 OK\r\n";
            }
        }

        if (!isset($this->head['Server'])) {
            $this->head['Server'] = 'kovey framework';
        }
        if (!isset($this->head['Content-Type'])) {
            $this->head['Content-Type'] = 'text/html; charset=utf-8';
        }
        if (!isset($this->head['Content-Length'])) {
            $this->head['Content-Length'] = strlen($this->body);
        }
        foreach($this->head as $k=>$v) {
            $out .= $k . ': ' . $v . "\r\n";
        }
        if (!empty($this->cookie) && is_array($this->cookie)) {
            foreach($this->cookie as $v) {
                $out .= "Set-Cookie: $v\r\n";
            }
        }
        $out .= "\r\n";
        return $out;
    }

    /**
     * @description no cache
     *
     * @return void
     */
    public function noCache() : void
    {
        $this->head['Cache-Control'] = 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0';
        $this->head['Pragma'] = 'no-cache';
    }

    /**
     * @description get body
     *
     * @return string
     */
    public function getBody() : string
    {
        return $this->body;
    }

    /**
     * @description get head
     *
     * @return Array
     */
    public function getHead() : Array
    {
        return $this->head;
    }

    /**
     * @description get cookie
     *
     * @return Array
     */
    public function getCookie() : Array
    {
        return $this->cookie;
    }

    /**
     * @description set body
     *
     * @param string $body
     *
     * @return void
     */
    public function setBody(string $body) : void
    {
        $this->body = $body;
        $this->head['Content-Length'] = strlen($this->body);
    }

    /**
     * @description to array
     *
     * @return Array
     */
    public function toArray() : array
    {
        return array(
            'httpCode' => $this->status,
            'content' => $this->body,
            'header' => $this->getHead(),
            'cookie' => $this->getCookie()
        );
    }

    /**
     * @description clear body
     *
     * @return void
     */
    public function clearBody() : void
    {
        $this->body = '';
        $this->head['Content-Length'] = 0;
    }

    /**
     * @description get http code
     *
     * @return int
     */
    public function getHttpCode() : int
    {
        return $this->status;
    }
}
