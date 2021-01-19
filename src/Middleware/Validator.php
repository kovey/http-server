<?php
/**
 *
 * @description 数据验证中间件
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

class Validator implements MiddlewareInterface
{
    /**
     * @description 规则
     *
     * @var Array
     */
    private Array $rules = array();

    /**
     * @description 设置验证规则
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
     * @description 中间件的具体实现
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

        $data = null;
        switch (strtolower($event->getRequest()->getMethod())) {
            case 'get':
                $data = $event->getRequest()->getQuery();
                break;
            case 'post':
                $data = $event->getRequest()->getPost();
                break;
            case 'put':
                $data = $event->getRequest()->getPut();
                break;
            case 'delete':
                $data = $event->getRequest()->getDelete();
                break;
            default:
                $data = array();
        }

        $valid = new Valid($data);
        foreach ($this->rules as $rule) {
            $valid->addRule($rule);
        }

        if (!$valid->valid()) {
            $event->getResponse()->status(200);
            $event->getResponse()->setHeader('content-type', 'application/json');
            $event->getResponse()->setBody(Json::encode(array(
                'code' => 1000,
                'msg' => $valid->getError()
            )));
            return $event->getResponse();
        }

        return $next($event);
    }
}
