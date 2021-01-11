<?php
/**
 *
 * @description 简单的开启session中间件
 *
 * @package     
 *
 * @time        2019-10-20 20:15:25
 *
 * @author      kovey
 */
namespace Kovey\Web\Middleware;

use Kovey\Web\App\Http\Session\File;
use Kovey\Library\Config\Manager;
use Kovey\Event\EventInterface;
use Kovey\Pipeline\Middleware\MiddlewareInterface;

class SessionStart implements MiddlewareInterface
{
    /**
     * @description 中间件的具体实现
     *
     * @param EventInterface $event
     *
     * @param callable | Array $next
     *
     * @return mixed
     */
    public function handle(EventInterface $event, callable | Array $next)
    {
        $cookie = $event->getRequest()->getCookie();
        $sessionId = $cookie['kovey_session_id'] ?? '';
        $session = new File(Manager::get('server.session.dir'), $sessionId);
        $event->getResponse()->setCookie('kovey_session_id', $session->getSessionId(), strtotime('+1 Hour'));
        $event->getRequest()->setSession($session);

        return $next($event);
    }
}
