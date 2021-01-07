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

class Console implements EventInterface
{
    private string $path;

    private string $method;

    private Array $args;

    private string $traceId;

    public function __construct(string $path, string $method, Array $args, string $traceId)
    {
        $this->path = $path;
        $this->method = $method;
        $this->args = $args;
        $this->traceId = $traceId;
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

    public function getPath() : string
    {
        return $this->path;
    }

    public function getMethod() : string
    {
        return $this->method;
    }

    public function getArgs() : Array
    {
        return $this->args;
    }

    public function getTraceId() : string
    {
        return $this->traceId;
    }
}
