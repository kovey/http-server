<?php
/**
 * @description
 *
 * @package
 *
 * @author kovey
 *
 * @time 2021-01-06 19:00:34
 *
 */
namespace Kovey\Web\Event;

use Kovey\Event\EventInterface;
use Kovey\Web\Server\Server;

class StartedAfter implements EventInterface
{
    private Server $server;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    /**
     * @description propagation stopped
     *
     * @return bool
     */
    public function isPropagationStopped() : bool
    {
        return true;
    }

    /**
     * @description stop propagation
     *
     * @return EventInterface
     */
    public function stopPropagation() : EventInterface
    {
        return $this;
    }

    public function getServer() : Server
    {
        return $this->server;
    }
}
