<?php
/**
 *
 * @description 文件session
 *
 * @package     Session
 *
 * @time        2019-10-12 23:14:43
 *
 * @author      kovey
 */
namespace Kovey\Web\App\Http\Session;
use Swoole\Coroutine\System;
use Kovey\Library\Util\Json;

class File implements SessionInterface
{
	/**
	 * @description 内容
	 *
	 * @var Array
	 */
	private Array $content;

	/**
	 * @description 文件
	 *
	 * @var string
	 */
	private string $file;

	/**
	 * @description ID
	 *
	 * @var string
	 */
	private string $sessionId;

	/**
	 * @description 目录
	 *
	 * @var string
	 */
	private string $dir;

	/**
	 * @description 构造
	 *
	 * @param string $dir
	 *
	 * @param string $sessionId
	 *
	 * @return File
	 */
	public function __construct(string $dir, string $sessionId)
	{
		$this->dir = $dir;

		$this->file = $dir . '/' . str_replace(array('$', '/', '.'), '', $sessionId);

		$this->sessionId = $sessionId;
		$this->content = array();

		$this->init();
	}

	/**
	 * @description 初始化
	 *
	 * @return null
	 */
	private function init()
	{
		if (!empty($this->sessionId)) {
			if (!is_file($this->file)) {
				$this->newSessionId();
				return;
			}
		}

		$file = System::readFile($this->file);
		if (empty($file)) {
            $this->content = array();
			return;
		}

		$this->content = Json::decode($file);
	}

	/**
	 * @description 获取值
	 *
	 * @param string $name
	 *
	 * @return mixed
	 */
	public function get(string $name)
	{
		return $this->content[$name] ?? null;
	}

	/**
	 * @description 设置值
	 *
	 * @param string $name
	 *
	 * @param mixed $val
	 *
	 * @return null
	 */
	public function set(string $name, $val)
	{
		$this->content[$name] = $val;
	}

	/**
	 * @description 保存到REDIS
	 *
	 * @return null
	 */
	private function saveToFile()
	{
		go (function () {
			System::writeFile($this->file, Json::encode($this->content));
		});
	}

	/**
	 * @description 获取值
	 *
	 * @param string $name
	 *
	 * @return mixed
	 */
	public function __get(string $name)
	{
		return $this->get($name);
	}

	/**
	 * @description 设置值
	 *
	 * @param string $name
	 *
	 * @param mixed $val
	 *
	 * @return null
	 */
	public function __set(string $name, $val)
	{
		$this->set($name, $val);
	}

	/**
	 * @description 删除
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public function del(string $name) : bool
	{
		if (!isset($this->content[$name])) {
			return false;
		}

		$this->set($name, null);
		return true;
	}

	/**
	 * @description 获取sessionID
	 *
	 * @return string
	 */
	public function getSessionId() : string
	{
		if (!empty($this->sessionId)) {
			return $this->sessionId;
		}

		$this->newSessionId();

		return $this->sessionId;
	}

	/**
	 * @description 创建sessionID
	 *
	 * @return null
	 */
	private function newSessionId()
	{
		$this->sessionId = password_hash(uniqid('session', true) . random_int(1000000, 9999999), PASSWORD_DEFAULT);
		$this->file = $this->dir . '/' . str_replace(array('$', '/', '.'), '', $this->sessionId); 
	}

	/**
	 * @description 清除
	 *
	 * @return null
	 */
	public function clear()
	{
		$this->content = array();
	}

	/**
	 * @description 一些处理
	 *
	 * @return null
	 */
	public function __destruct()
	{
		$this->saveToFile();
	}
}
