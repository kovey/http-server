<?php
/**
 *
 * @description http 服务
 *
 * @package     Web\Server
 *
 * @time        Tue Sep 24 08:54:02 2019
 *
 * @author      kovey
 */
namespace Kovey\Web\Server;

use Swoole\Http\Response;
use Kovey\Logger\Logger;
use Kovey\Library\Util\Json;

class Server
{
    /**
     * @description 服务器
     *
     * @var Swoole\Http\Server
     */
    private \Swoole\Http\Server $serv;

    /**
     * @description 配置
     *
     * @var Array
     */
    private Array $config;

    /**
     * @description 事件
     *
     * @var Array
     */
    private Array $events;

    /**
     * @description 允许的事件类型
     *
     * @var Array
     */
    private Array $eventsTypes;

    /**
     * @description 静态目录
     *
     * @var Array
     */
    private Array $staticDir;

    /**
     * @description 是否在docker中运行
     *
     * @var bool
     */
    private bool $isRunDocker;

    /**
     * @description 构造
     *
     * @param Array $config
     *
     * @return Server
     */
    public function __construct(Array $config)
    {
        $this->config = $config;
        $this->isRunDocker = ($this->config['run_docker'] ?? 'Off') === 'On';
        $this->serv = new \Swoole\Http\Server($this->config['host'], intval($this->config['port']));
        $this->events = array();
        $this->eventsTypes = array(
            'startedBefore' => 1, 
            'startedAfter' => 1, 
            'workflow' => 1, 
            'init' => 1, 
            'console' => 1,
            'monitor' => 1
        );

        $this->init();
    }

    /**
     * @description 事件监听
     *
     * @param string $name
     *
     * @param $callback
     *
     * @return Server
     */
    public function on(string $name, $callback)
    {
        if (!isset($this->eventsTypes[$name])) {
            return $this;
        }

        if (!is_callable($callback)) {
            return $this;
        }

        $this->events[$name] = $callback;
        return $this;
    }

    /**
     * @description 初始化
     *
     * @return Server
     */
    private function init() : Server
    {
        $logDir = dirname($this->config['log_file']);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $logDir = dirname($this->config['pid_file']);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $this->serv->set(array(
            'daemonize' => !$this->isRunDocker,
            'http_compression' => true,
            'enable_static_handler' => true,
            'document_root' => APPLICATION_PATH . $this->config['document_root'],
            'pid_file' => $this->config['pid_file'] ?? '/var/run/kovey.pid',
            'log_file' => $this->config['log_file'],
            'worker_num' => $this->config['worker_num'],
            'enable_coroutine' => true,
            'max_coroutine' => $this->config['max_co'],
            'package_max_length' => $this->getBytes($this->config['package_max_length'])
        ));

        $this->scanStaticDir();

        if (isset($this->events['startedBefore'])) {
            call_user_func($this->events['startedBefore'], $this);
        }

        $this->initCallBack();
        return $this;
    }

    /**
     * @description string to bytes
     *
     * @param string $num
     *
     * @return int
     */
    private function getBytes(string $num) : int
    {
        $unit = strtoupper(substr($num, -1));
        $num = intval(substr($num, 0, -1));
        if ($unit === 'B') {
            return $num;
        }

        if ($unit === 'K') {
            return $num * 1024;
        }

        if ($unit === 'M') {
            return $num * 1024 * 1024;
        }

        if ($unit === 'G') {
            return $num * 1024 * 1024 * 1024;
        }

        return 0;
    }

    /**
     * @description 扫描静态资源目录
     *
     * @return null
     */
    private function scanStaticDir()
    {
        $this->staticDir = array();

        $dir = APPLICATION_PATH . $this->config['document_root'];
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) as $d) {
            if ($d === '.' || $d === '..') {
                continue;
            }

            $this->staticDir[] = $d;
        }
    }

    /**
     * @description 初始化回调
     *
     * @return Server
     */
    private function initCallBack() : Server
    {
        $this->serv->on('workerStart', array($this, 'workerStart'));
        $this->serv->on('managerStart', array($this, 'managerStart'));
        $this->serv->on('request', array($this, 'request'));
        $this->serv->on('close', array($this, 'close'));
        $this->serv->on('pipeMessage', array($this, 'pipeMessage'));

        return $this;
    }

    /**
     * @description 监听进程间通讯
     *
     * @param Swoole\Http\Server $serv
     *
     * @param int $workerId
     *
     * @param mixed $data
     *
     * @return null
     */
    public function pipeMessage(\Swoole\Http\Server $serv, int $workerId, $data)
    {
        try {
            if (!isset($this->events['console'])) {
                return;
            }

            go(function () use ($data, $workerId) {
                call_user_func($this->events['console'], $data['p'] ?? '', $data['m'] ?? '', $data['a'] ?? array(), $data['t'] ?? '');
            });
        } catch (\Throwable $e) {
            Logger::writeExceptionLog(__LINE__, __FILE__, $e, $data['t'] ?? '');
        }
    }

    /**
     * @description Manager 进程启动
     *
     * @param Swoole\Http\Server $serv
     *
     * @return null
     */
    public function managerStart(\Swoole\Http\Server $serv)
    {
        ko_change_process_name($this->config['name'] . ' master');
    }

    /**
     * @description Worker 进程启动
     *
     * @param Swoole\Http\Server $serv
     *
     * @param int $workerId
     *
     * @return null
     */
    public function workerStart(\Swoole\Http\Server $serv, $workerId)
    {
        ko_change_process_name($this->config['name'] . ' worker');

        if (!isset($this->events['init'])) {
            return;
        }

        call_user_func($this->events['init'], $this);
    }

    /**
     * @description 判断是否是静态资源目录
     *
     * @param string $uri
     *
     * @return bool
     */
    private function isStatic(string $uri) : bool
    {
        $info = explode('/', $uri);
        if (count($info) < 2) {
            return false;
        }

        return in_array($info[1], $this->staticDir, true);
    }

    /**
     * @description Worker 进程启动
     *
     * @param Swoole\Http\Request $request
     *
     * @param Swoole\Http\Response $response
     *
     * @return null
     */
    public function request(\Swoole\Http\Request $request, \Swoole\Http\Response $response)
    {
        if ($this->isStatic($request->server['request_uri'] ?? '')) {
            return;
        }

        if (!isset($this->events['workflow'])) {
            $response->status(ErrorTemplate::HTTP_CODE_500);
            $response->header('content-type', 'text/html');
            $response->end(ErrorTemplate::getContent(ErrorTemplate::HTTP_CODE_500));
            return;
        }

        $begin = microtime(true);
        $time = time();

        $result = array();
        $traceId = $this->getTraceId($request->server['request_uri']);
        try {
            $result = call_user_func($this->events['workflow'], $request, $traceId);
        } catch (\Throwable $e) {
            Logger::writeExceptionLog(__LINE__, __FILE__, $e, $traceId);
            $result = array(
                'httpCode' => ErrorTemplate::HTTP_CODE_500,
                'header' => array(
                    'content-type' => 'text/html'
                ),
                'content' => ErrorTemplate::getContent(ErrorTemplate::HTTP_CODE_500),
                'cookie' => array()
            );
        }

        $httpCode = $result['httpCode'] ?? ErrorTemplate::HTTP_CODE_500;
        $response->status($httpCode);

        $header = $result['header'] ?? array();
        foreach ($header as $k => $v) {
            $response->header($k, $v);
        }

        $response->header('Request-Id', $traceId);

        $cookie = $result['cookie'] ?? array();
        foreach ($cookie as $cookie) {
            $response->header('Set-Cookie', $cookie);
        }

        $body = $result['content'] ?? ErrorTemplate::getContent($httpCode);
        $response->end($body);
        $this->monitor(
            $begin, microtime(true), $request->server['request_uri'] ?? '/', $this->getData($request), $request->server['remote_addr'] ?? '', $time, 
            $httpCode, $body, $traceId, $result['class'] ?? '', $result['method'] ?? '', $request->server['request_method']
        );
    }

    /**
     * @description get origin data
     *
     * @param \Swoole\Http\Request $request
     *
     * @return string
     */
    private function getData(\Swoole\Http\Request $request) : string
    {
        $params = $request->getContent();
        if (!empty($params)) {
            return $params;
        }

        if (!empty($request->get)) {
            return Json::encode($request->get);
        }

        return Json::encode(empty($request->post) ? array() : $request->post);
    }

    /**
     * @description monitor
     *
     * @param float $begin
     *
     * @param float $end
     *
     * @param string $uri
     *
     * @param string $params
     *
     * @param string $ip
     *
     * @param int $time
     *
     * @param int $code
     *
     * @param string $body
     *
     */
    private function monitor(float $begin, float $end, string $uri, string $params, string $ip, int $time, int $code, string $body, string $traceId, string $class, string $method, string $reqMethod)
    {
        if (!isset($this->events['monitor'])) {
            return;
        }

        try {
            call_user_func($this->events['monitor'], array(
                'delay' => round(($end - $begin) * 1000, 2),
                'path' => $uri,
                'request_method' => $reqMethod,
                'params' => $params,
                'request_time' => $begin * 10000,
                'service' => $this->config['name'],
                'class' => $class,
                'method' => $method,
                'ip' => $ip,
                'time' => $time,
                'timestamp' => date('Y-m-d H:i:s', $time),
                'minute' => date('YmdHi', $time),
                'http_code' => $code,
                'response' => $body,
                'traceId' => $traceId,
                'from' => $this->config['name'],
                'end' => $end * 10000
            ));
        } catch (\Throwable $e) {
            Logger::writeExceptionLog(__LINE__, __FILE__, $e, $traceId);
        }
    }

    /**
     * @description 启动
     *
     * @return null
     */
    public function start()
    {
        $this->serv->start();
    }

    /**
     * @description 链接关闭
     *
     * @param Swoole\Http\Server $server
     *
     * @param int $fd
     *
     * @param int $reactorId
     *
     * @return null
     */
    public function close($server, int $fd, int $reactorId)
    {}

    /**
     * @description 获取服务器对象
     *
     * @return Swoole\Http\Server
     */
    public function getServ() : \Swoole\Http\Server
    {
        return $this->serv;
    }

    /**
     * @description get trace id
     *
     * @param string $prefix
     *
     * @return string
     */
    public function getTraceId(string $prefix) : string
    {
        return hash('sha256', uniqid($prefix, true) . random_int(1000000, 9999999));
    }
}
