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

class Monitor implements EventInterface
{
    private Array $data;

    public function __construct(Array $data)
    {
        $this->data = $data;
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

    public function getData() : Array
    {
        return $this->data;
    }
}
