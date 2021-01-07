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
use Swoole\Http\Request;
use Swoole\Http\Response;

class Workflow implements EventInterface
{
    private Request $request;

    private Response $response;

    private string $traceId;

    public function __construct(Request $request, Response $response, string $traceId)
    {
        $this->request = $request;
        $this->response = $response;
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

    public function getRequest() : Request
    {
        return $this->request;
    }

    public function getResponse() : Response
    {
        return $this->response;
    }

    public function getTraceId() : string
    {
        return $this->traceId;
    }
}
