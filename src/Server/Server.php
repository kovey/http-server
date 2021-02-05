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
use Kovey\Web\Event;
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
    public function request(Request $request, Response $response) : void
    {
        if ($this->isStatic($request->server['request_uri'] ?? '')) {
            return;
        }

        $business = new Business($request, $response, $this->config['name']);
        $business->begin()
                 ->run($this->event)
                 ->end()
                 ->monitor($this);
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
}
