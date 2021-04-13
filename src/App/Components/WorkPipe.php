<?php
/**
 * @description
 *
 * @package
 *
 * @author kovey
 *
 * @time 2021-02-01 14:29:01
 *
 */
namespace Kovey\Web\App\Components;

use Kovey\Web\Exception;
use Kovey\Web\Event;
use Kovey\Web\App\Http\Router\RouterInterface;
use Kovey\Web\App\Http\Router\RoutersInterface;
use Kovey\Web\App\Mvc\Controller\ControllerInterface;
use Kovey\Web\App\Http\Response\ResponseInterface;
use Kovey\Web\App\Http\Request\RequestInterface;
use Kovey\Logger\Logger;
use Kovey\App\Components\Work;
use Kovey\Pipeline\Middleware\MiddlewareInterface;
use Kovey\Event\EventInterface;
use Kovey\Web\Server\ErrorTemplate;
use Kovey\Connection\ManualCollectInterface;

class WorkPipe extends Work
{
    private string $controllerPath;

    private string $viewPath;

    private string $templateSuffix;

    private Array $plugins;

    private RoutersInterface $routers;

    /**
     * @description default middleware
     *
     * @var Array
     */
    private Array $defaultMiddlewares;

    public function __construct(string $controllerPath, string $viewPath, string $templateSuffix)
    {
        $this->controllerPath =  $controllerPath;
        $this->viewPath = $viewPath;
        $this->templateSuffix = $templateSuffix;
        $this->plugins = array();
        $this->defaultMiddlewares = array();
    }

    public function setRouters(RoutersInterface $routers) : WorkPipe
    {
        $this->routers = $routers;
        return $this;
    }

    public function addRouter(string $uri, RouterInterface $router) : WorkPipe
    {
        $this->routers->addRouter($uri, $router);
        return $this;
    }

    public function addPlugin(string $plugin) : WorkPipe
    {
        $this->plugins[$plugin] = $plugin;
        return $this;
    }

    private function getControllerObject(Event\Pipeline $event) : ControllerInterface
    {
        $router = $event->getRouter();
        $conFile = APPLICATION_PATH . '/' . $this->controllerPath . $router->getClassPath();
        if (!is_file($conFile)) {
            throw new Exception\PageNotFoundException("file of " . $router->getController() . " is not exists, controller file \" $conFile\".");
        }

        $keywords = null;
        $action = $router->getActionName();
        try {
            $keywords = $this->container->getKeywords($router->getClassName(), $action);
        } catch (\ReflectionException $e) {
            throw new Exception\PageNotFoundException($e->getMessage());
        }

        $obj = $this->container->get($router->getClassName(), $event->getTraceId(), $event->getSpanId(), $keywords['ext'], $event->getRequest(), $event->getResponse(), $this->plugins);
        if (!$obj instanceof ControllerInterface) {
            throw new Exception\PageNotFoundException("class " . $router->getClassName() . " is not extends Kovey\Web\App\Mvc\Controller\ControllerInterface.");
        }
        
        $obj->keywords = $keywords;
        if ($keywords['openTransaction']) {
            $obj->openTransaction();
        }

        if ($router->isViewDisabled()) {
            $obj->disableView();
        }

        if ($router->isPluginDisabled()) {
            $obj->disablePlugin();
        } else {
            $obj->setLayoutPath($router->getLayout(), $router->getLayoutDir());
        }

        return $obj;
    }

    private function checkRedirect(ControllerInterface $obj) : bool
    {
        $httpCode = $obj->getResponse()->getHttpCode();
        return $httpCode == 201 
            || ($httpCode > 300 && $httpCode < 400);
    }

    private function initView(ControllerInterface $obj, RouterInterface $router) : void
    {
        if ($obj->isViewDisabled()) {
            return;
        }

        $template = APPLICATION_PATH . '/' . $this->viewPath . '/' . $router->getViewPath() . '.' . $this->templateSuffix;
        $this->event->dispatch(new Event\View($obj, $template));
    }

    private function triggerAction(ControllerInterface $obj, Event\Pipeline $event) : ?string
    {
        $router = $event->getRouter();
        $params = array_merge($this->container->getMethodArguments($router->getClassName(), $router->getActionName(), $event->getTraceId()), $event->getParams());
        if ($this->event->listened('run_action')) {
            return $this->event->dispatchWithReturn(new Event\RunAction(
                $obj, $router->getActionName(), $params
            ));
        }

        return call_user_func(array($obj, $router->getActionName()), ...$params);
    }

    private function getContent(ControllerInterface $obj, Event\Pipeline $event) : ?string
    {
        try {
            if (!$obj->isOpenTransaction()) {
                return $this->triggerAction($obj, $event);
            }

            $obj->database->beginTransaction();
            try {
                $content = $this->triggerAction($obj, $event);
                $obj->database->commit();
            } catch (\Throwable $e) {
                $obj->database->rollBack();
                throw $e;
            }
        } catch (\Throwable $e) {
            throw $e;
        } finally {
            foreach ($obj->keywords as $value) {
                if ($value instanceof ManualCollectInterface) {
                    $value->collect();
                }
            }
        }

        return $content;
    }

    private function viewRender(ControllerInterface $obj, Event\Pipeline $event, ?string $content) : Array
    {
        if ($obj->isViewDisabled()) {
            $event->getResponse()->setBody($content);
            $event->getResponse()->status(200);
            return $event->getResponse()->toArray();
        }

        if ($this->checkRedirect($obj)) {
            return $event->getResponse()->toArray();
        }

        $obj->render();

        if (!$obj->isPluginDisabled()) {
            foreach ($obj->getPlugins() as $plugin) {
                $plugin->loopShutdown($event->getRequest(), $event->getResponse());
            }
        }

        $event->getResponse()->status(200);
        return $event->getResponse()->toArray();
    }

    private function callRouter(Event\Pipeline $event) : Array
    {
        $event->getResponse()->setBody(call_user_func($event->getRouter()->getCallable(), $event->getRequest(), $event->getResponse()));
        $event->getResponse()->status(200);
        return $event->getResponse()->toArray();
    }

    public function runWorkPipe(Event\Pipeline $event) : Array
    {
        if (!empty($event->getRouter()->getCallable())) {
            return $this->callRouter($event);
        }

        $obj = $this->getControllerObject($event);
        if ($this->checkRedirect($obj)) {
            return $obj->getResponse()->toArray();
        }

        $this->initView($obj, $event->getRouter());

        return $this->viewRender($obj, $event, $this->getContent($obj, $event));
    }

    public function run(EventInterface $event) : Array
    {
        $req = $this->event->dispatchWithReturn(new Event\Request($event->getRequest()));
        if (!$req instanceof RequestInterface) {
            throw new Exception\InternalException('request is not implements Kovey\Web\App\Http\Request\RequestInterface.');
        }

        $res = $this->event->dispatchWithReturn(new Event\Response());
        if (!$res instanceof ResponseInterface) {
            throw new Exception\InternalException('request is not implements Kovey\Web\App\Http\Response\ResponseInterface.');
        }

        $uriInfo = $this->getRealUri($req->getUri());
        $router = $this->routers->getRouter($uriInfo['uri'], $req->getMethod());
        if ($router === null) {
            throw new Exception\MethodDisabledException('router is error, uri: ' . $uriInfo['uri']);
        }

        if (count($router->getParamFields()) > count($uriInfo['params'])) {
            throw new Exception\MethodDisabledException('router params is error, uri: ' . $uriInfo['uri']);
        }

        $params = array();
        foreach ($router->getParamFields() as $id => $field) {
            $params[] = $uriInfo['params'][$id];
        }

        $req->setController($router->getController())
            ->setAction($router->getAction());

        $result = array(
            'header' => array(
                'content-type' => 'text/html'
            ),
            'cookie' => array()
        );

        try {
            $result = $this->event->dispatchWithReturn(new Event\Pipeline($req, $res, $router, $event->getTraceId(), $params, $event->getSpanId()));
            if ($result instanceof ResponseInterface) {
                $result = $result->toArray();
            }
        } catch (Exception\PageNotFoundException $e) {
            Logger::writeExceptionLog(__LINE__, __FILE__, $e, $event->getTraceId());
            $result['httpCode'] = ErrorTemplate::HTTP_CODE_404;
            $result['content'] = ErrorTemplate::getContent(ErrorTemplate::HTTP_CODE_404);
            $result['trace'] = $e->getTraceAsString();
            $result['err'] = $e->getMessage();
        } catch (\Throwable $e) {
            Logger::writeExceptionLog(__LINE__, __FILE__, $e, $event->getTraceId());
            $result['httpCode'] = ErrorTemplate::HTTP_CODE_500;
            $result['content'] = ErrorTemplate::getContent(ErrorTemplate::HTTP_CODE_500);
            $result['trace'] = $e->getTraceAsString();
            $result['err'] = $e->getMessage();
        }

        $result['class'] = $router->getController() . 'Controller';
        $result['method'] = $router->getAction() . 'Action';
        return $result;
    }

    public function disableDefaultRouter() : WorkPipe
    {
        $this->routers->disableDefault();
        return $this;
    }

    public function addMiddleware(MiddlewareInterface $middleware) : WorkPipe
    {
        $this->defaultMiddlewares[] = $middleware;
        return $this;
    }

    /**
     * @description get default middlewares
     *
     * @return Array
     */
    public function getMiddlewares() : Array
    {
        return $this->defaultMiddlewares;
    }

    private function getRealUri(string $uri) : Array
    {
        $uri = trim($uri);
        $info = explode('/', $uri);
        $count = count($info);
        $params = array();
        if ($count <= 3) {
            return array(
                'uri' => $uri,
                'params' => $params
            );
        }

        for ($i = 3; $i < $count; $i ++) {
            $params[] = $info[$i];
        }

        return array(
            'uri' => '/' . $info[1] . '/' . $info[2],
            'params' => $params
        );
    }
}
