<?php
/**
 *
 * @description response interface
 *
 * @package     Response
 *
 * @time        2019-10-17 23:30:36
 *
 * @author      kovey
 */
namespace Kovey\Web\App\Http\Response;

interface ResponseInterface
{
    /**
     * @description set status
     *
     * @param int $code
     *
     * @return ResponseInterface
     */
    public function status(int $code) : ResponseInterface;

    /**
     * @description redirect
     *
     * @param string $url
     *
     * @return ResponseInterface
     */
    public function redirect(string $url) : ResponseInterface;

    /**
     * @description set header
     *
     * @param string $key
     *
     * @param string $val
     *
     * @return ResponseInterface
     */
    public function setHeader(string $key, string $value) : ResponseInterface;

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
     * @param bool $httponly
     *
     * @return ResponseInterface
     */
    public function setCookie(
        string $name, ?string $value = null, ?string $expire = null, string $path = '/', ?string $domain = null, ?string $secure = null, ?string $httponly = null
    ) : ResponseInterface;

    /**
     * @description add headers
     *
     * @param Array $header
     *
     * @return ResponseInterface
     */
    public function addHeaders(array $header) : ResponseInterface;

    /**
     * @description get header
     *
     * @param bool $fastcgi
     *
     * @return string
     */
    public function getHeader(bool $fastcgi = false) : string;

    /**
     * @description no cache
     *
     * @return void
     */
    public function noCache() : void;

    /**
     * @description get body
     *
     * @return string
     */
    public function getBody() : string;

    /**
     * @description get head
     *
     * @return Array
     */
    public function getHead() : Array;

    /**
     * @description get cookie
     *
     * @return Array
     */
    public function getCookie() : Array;

    /**
     * @description set body
     *
     * @param string $body
     *
     * @return void
     */
    public function setBody(string $body) : void;

    /**
     * @description to array
     *
     * @return Array
     */
    public function toArray() : array;

    /**
     * @description clear body
     *
     * @return void
     */
    public function clearBody() : void;

    /**
     * @description get status
     *
     * @return int
     */
    public function getHttpCode() : int;
}
