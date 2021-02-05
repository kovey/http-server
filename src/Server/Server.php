<?php
/**
 *
 * @description http server
 *
 * @package     Web\Server
 *
 * @time        Tue Sep 24 08:54:02 2019
 *
 * @author      kovey
 */
namespace Kovey\Web\Server;

use Swoole\Http\Response;
use Swoole\Http\Request;
use Kovey\Logger\Logger;
use Kovey\Library\Util\Json;
use Kovey\Web\Event;
use Kovey\Web\Exception\MethodDisabledException;
use Kovey\App\Components\ServerAbstract;

class Server extends ServerAbstract
{
    /**
     * @description static dir
     *
     * @var Array
     */
    private Array $staticDir;

    /**
     * @description run on docker ?
     *
     * @var bool
     */
    private bool $isRunDocker;

    /**
     * @description construct
     *
     * @param Array $config
     *
     * @return Server
     */
    protected function initServer()
    {
        $this->isRunDocker = ($this->config['run_docker'] ?? 'Off') === 'On';
        $this->serv = new \Swoole\Http\Server($this->config['host'], intval($this->config['port']));
        $this->event->addSupportEvents(array(
            'startedBefore' => Event\StartedBefore::class, 
            'startedAfter' => Event\StartedAfter::class, 
            'workflow' => Event\Workflow::class
        ));

        $this->init();
    }

    /**
     * @description init
     *
     * @return Server
     */
    private function init() : Server
    {
        $logDir = dirname($this->config['pid_file']);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        if (!is_dir($this->config['logger_dir'] . '/server')) {
            mkdir($this->config['logger_dir'] . '/server');
        }

        $document = APPLICATION_PATH . $this->config['document_root'];
        if (!is_dir($document)) {
            $document = '';
        }

        $this->serv->set(array(
            'daemonize' => !$this->isRunDocker,
            'http_compression' => true,
            'enable_static_handler' => true,
            'document_root' => $document,
            'pid_file' => $this->config['pid_file'] ?? '/var/run/kovey.pid',
            'log_file' => $this->config['logger_dir'] . '/server/server.log',
            'worker_num' => $this->config['worker_num'],
            'enable_coroutine' => true,
            'max_coroutine' => $this->config['max_co'],
            'package_max_length' => $this->getBytes($this->config['package_max_length']),
            'event_object' => true,
            'log_rotation' => SWOOLE_LOG_ROTATION_DAILY,
            'log_date_format' => '%Y-%m-%d %H:%M:%S'
        ));

        $this->scanStaticDir();

        $event = new Event\StartedBefore($this);
        $this->event->dispatch($event);

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
     * @description scan static dir
     *
     * @return void
     */
    private function scanStaticDir() : void
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
     * @description init callback
     *
     * @return Server
     */
    private function initCallBack() : Server
    {
        $this->serv->on('request', array($this, 'request'));
        $this->serv->on('close', array($this, 'close'));

        return $this;
    }

    /**
     * @description is static resource
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
     * @description request event
     *
     * @param Swoole\Http\Request $request
     *
     * @param Swoole\Http\Response $response
     *
     * @return void
     */
    public function request(\Swoole\Http\Request $request, \Swoole\Http\Response $response) : void
    {
        if ($this->isStatic($request->server['request_uri'] ?? '')) {
            return;
        }

        $begin = microtime(true);
        $time = time();
        $trace = '';
        $err = '';

        $result = array(
            'header' => array(
                'content-type' => 'text/html'
            ),
            'cookie' => array()
        );

        $traceId = $this->getTraceId($request->server['request_uri']);
        try {
            $event = new Event\Workflow($request, $response, $traceId);
            $result = $this->event->dispatchWithReturn($event);
            $trace = $result['trace'] ?? '';
            $err = $result['err'] ?? '';
        } catch (MethodDisabledException $e) {
            $trace = $e->getTraceAsString();
            $err = $e->getMessage();
            Logger::writeExceptionLog(__LINE__, __FILE__, $e, $traceId);
            $result['httpCode'] = ErrorTemplate::HTTP_CODE_405;
            $result['content'] = ErrorTemplate::getContent(ErrorTemplate::HTTP_CODE_405);
        } catch (\Throwable $e) {
            $trace = $e->getTraceAsString();
            $err = $e->getMessage();
            Logger::writeExceptionLog(__LINE__, __FILE__, $e, $traceId);
            $result['httpCode'] = ErrorTemplate::HTTP_CODE_500;
            $result['content'] = ErrorTemplate::getContent(ErrorTemplate::HTTP_CODE_500);
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

        $body = $result['content'] ?? '';
        if ($httpCode >= ErrorTemplate::HTTP_CODE_400) {
            $body = ErrorTemplate::getContent($httpCode);
        }

        $response->end($body);
        if (!isset($this->config['monitor_open']) || $this->config['monitor_open'] !== 'Off') {
            $this->sendToMonitor(
                $begin, microtime(true), $request->server['request_uri'] ?? '/', $this->getData($request), $this->getClientIP($request), $time, 
                $httpCode, $body, $traceId, $result['class'] ?? '', $result['method'] ?? '', $request->server['request_method'], $trace, $err
            );
        }
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
     * @return void
     *
     */
    private function sendToMonitor(
        float $begin, float $end, string $uri, string $params, string $ip, int $time, int $code, string $body, string $traceId,
        string $class, string $method, string $reqMethod, string $trace, string $err
    ) : void
    {
        $this->monitor(array(
            'delay' => round(($end - $begin) * 1000, 2),
            'path' => $uri,
            'request_method' => $reqMethod,
            'params' => $params,
            'request_time' => $begin * 10000,
            'service' => $this->config['name'],
            'service_type' => 'http',
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
            'end' => $end * 10000,
            'trace' => $trace,
            'err' => $err
        ), $traceId);
    }

    /**
     * @description disconnect
     *
     * @param Swoole\Http\Server $server
     *
     * @param Swoole\Server\Event
     *
     * @return void
     */
    public function close(\Swoole\Http\Server $server, \Swoole\Server\Event $event) : void
    {}

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

    /**
     * @description get client ip
     *
     * @return string
     */
    public function getClientIP(\Swoole\Http\Request $request)
    {
        if (isset($request->header['x-real-ip'])) {
            return $request->header['x-real-ip'];
        }

        if (isset($request->header['x-forwarded-for'])) {
            return $request->header['x-forwarded-for'];
        }

        if (isset($request->header['client-ip'])) {
            return $request->header['client-ip'];
        }

        if (isset($request->header['remote_addr'])) {
            return $request->header['remote_addr'];
        }

        return '';
    }
}
