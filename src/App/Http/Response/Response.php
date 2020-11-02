<?php
/**
 *
 * @description Response HTTP数据返回的封装
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
	 * @description 构造
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
	 * @description 设置状态码
	 *
	 * @param int $code
	 *
	 * @return Response
	 */
    public function status($code) : ResponseInterface
    {
        $this->status = $code;
		return $this;
    }

	/**
	 * @description 跳转
	 *
	 * @param string $url
	 *
	 * @return Response
	 */
	public function redirect($url) : ResponseInterface
	{
		$this->status(302);
		$this->setHeader('Location', $url);
		return $this;
	}

	/**
	 * @description 设置头信息
	 *
	 * @param string $key
	 *
	 * @param string $val
	 *
	 * @return Response
	 */
    public function setHeader($key, $value) : ResponseInterface
    {
        $this->head[$key] = $value;
		return $this;
    }

	/**
	 * @description 设置cookie
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
	 * @param bool $httponly
	 *
	 * @return Response
	 */
    public function setCookie(string $name, ?string $value = null, ?string $expire = null, string $path = '/', ?string $domain = null, ?string $secure = null, ?string $httponly = null) : ResponseInterface
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
	 * @description 添加头
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
	 * @description 获取头信息
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
	 * @description 是否cache
	 *
	 * @return null
	 */
    public function noCache()
    {
        $this->head['Cache-Control'] = 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0';
        $this->head['Pragma'] = 'no-cache';
    }

	/**
	 * @description 获取BODY
	 *
	 * @return string
	 */
	public function getBody() : string
	{
		return $this->body;
	}

	/**
	 * @description 获取HEAD
	 *
	 * @return Array
	 */
	public function getHead() : Array
	{
		return $this->head;
	}

	/**
	 * @description 获取COOKIE
	 *
	 * @return Array
	 */
	public function getCookie() : Array
	{
		return $this->cookie;
	}

	/**
	 * @description 设置BODY
	 *
	 * @param string $body
	 *
	 * @return null
	 */
	public function setBody(string $body)
	{
		$this->body = $body;
		$this->head['Content-Length'] = strlen($this->body);
	}

	/**
	 * @description 转换成数组
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
	 * @description 清理BODY
	 *
	 * @return null
	 */
	public function clearBody()
	{
		$this->body = '';
		$this->head['Content-Length'] = 0;
	}

	/**
	 * @description 获取状态码Y
	 *
	 * @return int
	 */
	public function getHttpCode() : int
	{
		return $this->status;
	}
}
