<?php
/**
 * @description
 *
 * @package
 *
 * @author kovey
 *
 * @time 2021-02-05 15:43:48
 *
 */
namespace Kovey\Web\Server;

use Swoole\Http\Response;
use Swoole\Http\Request;
use Kovey\App\Components\BusinessInterface;
use Kovey\App\Components\ServerAbstract;
use Kovey\Logger\Logger;
use Kovey\Library\Util\Json;
use Kovey\Web\Event;
use Kovey\Web\Exception\MethodDisabledException;
use Kovey\Event\EventManager;

class Business implements BusinessInterface
{
    private Request $request;

    private Response $response;

    private float $beginTime;

    private int $time;

    private string $trace;

    private string $err;

    private Array $result;

    private string $traceId;

    private string $body;

    private int $httpCode;

    private string $name;

    public function __construct(Request $request, Response $response, string $name)
    {
        $this->name = $name;
        $this->request = $request;
        $this->response = $response;
        $this->trace = '';
        $this->err = '';
        $this->result = array(
            'header' => array(
                'content-type' => 'text/html'
            ),
            'cookie' => array()
        );
    }

    public function begin() : Business
    {
        $this->beginTime = microtime(true);
        $this->time = time();
        $this->traceId = $this->getTraceId();
        $this->body = '';
        $this->httpCode = ErrorTemplate::HTTP_CODE_200;

        return $this;
    }

    private function setCodeAndContent(int $code) : Business
    {
        $this->result['httpCode'] = $code;
        $this->result['content'] = ErrorTemplate::getContent($code);

        return $this;
    }

    public function run(EventManager $eventManager) : Business
    {
        try {
            $event = new Event\Workflow($this->request, $this->response, $this->traceId);
            $this->result = $eventManager->dispatchWithReturn($event);
            $this->trace = $this->result['trace'] ?? '';
            $this->err = $this->result['err'] ?? '';
        } catch (MethodDisabledException $e) {
            $this->trace = $e->getTraceAsString();
            $this->err = $e->getMessage();
            $this->setCodeAndContent(ErrorTemplate::HTTP_CODE_405);
            Logger::writeExceptionLog(__LINE__, __FILE__, $e, $this->traceId);
        } catch (\Throwable $e) {
            $trace = $e->getTraceAsString();
            $err = $e->getMessage();
            $this->setCodeAndContent(ErrorTemplate::HTTP_CODE_500);
            Logger::writeExceptionLog(__LINE__, __FILE__, $e, $this->traceId);
        }

        return $this;
    }

    public function end() : Business
    {
        $this->httpCode = $this->result['httpCode'] ?? ErrorTemplate::HTTP_CODE_500;
        $this->response->status($this->httpCode);

        $header = $this->result['header'] ?? array();
        foreach ($header as $k => $v) {
            $this->response->header($k, $v);
        }

        $this->response->header('Request-Id', $this->traceId);

        $cookie = $this->result['cookie'] ?? array();
        foreach ($cookie as $cookie) {
            $this->response->header('Set-Cookie', $cookie);
        }

        if ($this->httpCode >= ErrorTemplate::HTTP_CODE_400) {
            $this->body = ErrorTemplate::getContent($this->httpCode);
        } else {
            $this->body = $this->result['content'] ?? '';
        }

        $this->response->end($this->body);

        return $this;
    }

    /**
     * @description get origin data
     *
     * @return string
     */
    private function getData() : string
    {
        $params = $this->request->getContent();
        if (!empty($params)) {
            return $params;
        }

        if (!empty($this->request->get)) {
            return Json::encode($this->request->get);
        }

        return Json::encode(empty($this->request->post) ? array() : $this->request->post);
    }

    public function monitor(ServerAbstract $server) : Business
    {
        $end = microtime(true);
        $server->monitor(array(
            'delay' => round(($end - $this->beginTime) * 1000, 2),
            'path' => $this->request->server['request_uri'] ?? '/',
            'request_method' => $this->request->server['request_method'],
            'params' => $this->getData(),
            'request_time' => $this->beginTime * 10000,
            'service' => $this->name,
            'service_type' => 'http',
            'class' => $this->result['class'] ?? '',
            'method' => $this->result['method'] ?? '',
            'ip' => $this->getClientIP(),
            'time' => $this->time,
            'timestamp' => date('Y-m-d H:i:s', $this->time),
            'minute' => date('YmdHi', $this->time),
            'http_code' => $this->httpCode,
            'response' => $this->body,
            'traceId' => $this->traceId,
            'from' => $this->name,
            'end' => $end * 10000,
            'trace' => $this->trace,
            'err' => $this->err
        ), $this->traceId);

        return $this;
    }

    /**
     * @description get trace id
     *
     * @return string
     */
    public function getTraceId() : string
    {
        return hash('sha256', uniqid($this->request->server['request_uri'], true) . random_int(1000000, 9999999));
    }

    /**
     * @description get client ip
     *
     * @return string
     */
    public function getClientIP()
    {
        if (isset($this->request->header['x-real-ip'])) {
            return $this->request->header['x-real-ip'];
        }

        if (isset($this->request->header['x-forwarded-for'])) {
            return $this->request->header['x-forwarded-for'];
        }

        if (isset($this->request->header['client-ip'])) {
            return $this->request->header['client-ip'];
        }

        if (isset($this->request->header['remote_addr'])) {
            return $this->request->header['remote_addr'];
        }

        return '';
    }
}
