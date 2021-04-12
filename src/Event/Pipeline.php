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
use Kovey\Web\App\Http\Request\Request;
use Kovey\Web\App\Http\Response\Response;
use Kovey\Web\App\Http\Router\Router;

class Pipeline implements EventInterface
{
    private Request $request;

    private Response $response;

    private Router $router;

    private string $traceId;

    private Array $params;

    public function __construct(Request $request, Response $response, Router $router, string $traceId, Array $params)
    {
        $this->request = $request;
        $this->response = $response;
        $this->traceId = $traceId;
        $this->router = $router;
        $this->params = $params;
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

    public function getRouter() : Router
    {
        return $this->router;
    }

    public function getParams() : Array
    {
        return $this->params;
    }
}
