<?php
/**
 *
 * @description validator middleware
 *
 * @package     Middleware
 *
 * @time        2019-11-06 23:48:01
 *
 * @author      kovey
 */
namespace Kovey\Web\Middleware;

use Kovey\Event\EventInterface;
use Kovey\Pipeline\Middleware\MiddlewareInterface;
use Kovey\Validator\Validator as Valid;
use Kovey\Library\Util\Json;
use Kovey\Web\Server\ErrorTemplate;

class Validator implements MiddlewareInterface
{
    /**
     * @description rules
     *
     * @var Array
     */
    private Array $rules = array();

    /**
     * @description set rules
     *
     * @param Array $rules
     *
     * @return Validator
     */
    public function setRules(Array $rules) : Validator
    {
        $this->rules = $rules;
        return $this;
    }

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
        if (empty($this->rules)) {
            return $next($event);
        }

        $data = match (strtolower($event->getRequest()->getMethod())) {
            'get' => $event->getRequest()->getQuery(),
            'post' => $event->getRequest()->getPost(),
            'put' => $event->getRequest()->getPut(),
            'delete' => $event->getRequest()->getDelete(),
            default => array()
        };

        $valid = new Valid($data);
        foreach ($this->rules as $rule) {
            $valid->addRule($rule);
        }

        if ($valid->valid()) {
            return $next($event);
        }

        $event->getResponse()->status(ErrorTemplate::HTTP_CODE_200);
        $event->getResponse()->setHeader('content-type', 'application/json');
        $event->getResponse()->setBody(Json::encode(array(
            'code' => 1000,
            'msg' => $valid->getError(),
            'data' => new \ArrayObject()
        )));
        return $event->getResponse();
    }
}
