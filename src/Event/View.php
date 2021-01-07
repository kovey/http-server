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

class View implements EventInterface
{
    private ControllerInterface $controller;

    private string $template;

    public function __construct(ControllerInterface $controller, string $template)
    {
        $this->controller = $controller;
        $this->template = $template;
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

    public function getTemplate() : string
    {
        return $this->template;
    }
}
