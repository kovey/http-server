<?php
/**
 *
 * @description 放入Redis缓存
 *
 * @package     Session
 *
 * @time        2019-10-12 23:33:43
 *
 * @author      kovey
 */
namespace Kovey\Web\App\Http\Session;

use Kovey\Library\Util\Json;
use Kovey\Connection\Pool;

class Cache implements SessionInterface
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
     * @description 连接池
     *
     * @var PoolInterface
     */
    private Pool $pool;

    /**
     * @description 缓存见
     *
     * @var string
     */
    const SESSION_KEY = 'kovey_session_';

    /**
     * @description 构造
     *
     * @param PoolInterface $pool
     *
     * @param string $sessionId
     *
     * @return Cache
     */
    public function __construct(Pool $pool, string $sessionId)
    {
        $this->file = $sessionId;
        $this->pool = $pool;
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
        $redis = $this->pool->getConnection();
        if (!$redis) {
            return;
        }
        $file = $redis->get(self::SESSION_KEY . $this->file);
        if ($file === false) {
            $this->newSessionId();
            return;
        }

        $info = Json::decode($file);
        if (empty($info) || !is_array($info)) {
            return;
        }

        $this->content = $info;
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
    private function saveToRedis()
    {
        go (function () {
            $redis = $this->pool->getConnection();
            if (!$redis) {
                return;
            }

            $redis->set(self::SESSION_KEY . $this->file, Json::encode($this->content));
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
        if (!empty($this->file)) {
            return $this->file;
        }

        $this->newSessionId();

        return $this->file;
    }

    /**
     * @description 创建sessionID
     *
     * @return null
     */
    private function newSessionId()
    {
        $this->file = password_hash(uniqid('session', true) . random_int(1000000, 9999999), PASSWORD_DEFAULT);
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
        $this->saveToRedis();
    }
}
