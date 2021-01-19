<?php
/**
 * @description process cors
 *
 * @package Middleware
 *
 * @author kovey
 *
 * @time 2020-09-30 10:50:57
 *
 */
namespace Kovey\Web\Middleware;

use Kovey\Event\EventInterface;
use Kovey\Pipeline\Middleware\MiddlewareInterface;

class Cors implements MiddlewareInterface
{
    /**
     * @description handle
     *
     * @param EventInterface $event
     *
     * @param callable | Array $next
     *
     * @return mixed
     */
    public function handle(EventInterface $event, callable | Array $next) : mixed
    {
        $event->getRequest()->processCors();
        return $next($event);
    }
}
