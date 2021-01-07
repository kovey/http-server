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
use Kovey\Web\App\Mvc\Controller\ControllerInterface;

class RunAction implements EventInterface
{
    private ControllerInterface $controller;

    private string $action;

    private Array $args;

    public function __construct(ControllerInterface $controller, string $action, Array $args)
    {
        $this->controller = $controller;
        $this->action = $action;
        $this->args = $args;
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

    public function getController() : ControllerInterface
    {
        return $this->controller;
    }

    public function getAction() : string
    {
        return $this->action;
    }

    public function getArgs() : Array
    {
        return $this->args;
    }
}
