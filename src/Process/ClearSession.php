<?php
/**
 *
 * @description session clean
 *
 * @package     Web\Process
 *
 * @time        2019-10-13 01:19:20
 *
 * @author      kovey
 */
namespace Kovey\Web\Process;

use Kovey\Logger\Logger;
use Kovey\Library\Config\Manager;
use Kovey\Process\ProcessAbstract;
use Swoole\Timer;

class ClearSession extends ProcessAbstract
{
    /**
     * @description init
     *
     * @return null
     */
    protected function init() : void
    {
        $this->processName = Manager::get('server.server.name') . ' core clear session';
    }

    /**
     * @description business process
     *
     * @return void
     */
    protected function busi() : void
    {
        $this->listen(function ($pipe) {
            $result = $this->read();
        });

        Timer::tick(Manager::get('server.sleep.session') * 1000, function () {
            $sessionPath = Manager::get('server.session.dir');
            foreach (scandir($sessionPath) as $path) {
                if ($path == '.' || $path == '..') {
                    continue;
                }

                $file = $sessionPath . '/' . $path;
                clearstatcache(true, $file);

                $time = filemtime($file) + intval(Manager::get('server.session.expire'));
                if ($time > time()) {
                    continue;
                }

                unlink($sessionPath . '/' . $path);
            }

            Logger::writeInfoLog(__LINE__, __FILE__, 'clear session expired');
        });
    }
}
