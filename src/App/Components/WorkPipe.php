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
use Kovey\Container\ContainerInterface;
use Kovey\Web\App\Http\Response\ResponseInterface;
use Kovey\Web\App\Http\Request\RequestInterface;
use Kovey\Logger\Logger;
use Kovey\App\Components\Work;
use Kovey\Pipeline\Middleware\MiddlewareInterface;
use Kovey\Event\EventInterface;
use Kovey\Web\Server\ErrorTemplate;

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

        $action = $router->getActionName();
        try {
            $objectExt = $this->container->getKeywords($router->getClassName(), $action);
        } catch (\ReflectionException $e) {
            throw new Exception\PageNotFoundException($e->getMessage());
        }

        $obj = $this->container->get($router->getClassName(), $event->getTraceId(), $objectExt['ext'], $event->getRequest(), $event->getResponse(), $this->plugins);
        if (!$obj instanceof ControllerInterface) {
            throw new Exception\PageNotFoundException("class " . $router->getClassName() . " is not extends Kovey\Web\App\Mvc\Controller\ControllerInterface.");
        }

        if ($objectExt['openTransaction']) {
            $obj->openTransaction();
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
        if ($this->event->listened('run_action')) {
            return $this->event->dispatchWithReturn(new Event\RunAction(
                $obj, $router->getActionName(), $this->container->getMethodArguments($router->getClassName(), $router->getActionName(), $event->getTraceId())
            ));
        }

        return call_user_func(array($obj, $router->getActionName()), ...$this->container->getMethodArguments($router->getClassName(), $router->getActionName(), $event->getTraceId()));
    }

    private function getContent(ControllerInterface $obj, Event\Pipeline $event) : ?string
    {
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

        $uri = trim($req->getUri());
        $router = $this->routers->getRouter($uri, $req->getMethod());
        if ($router === null) {
            throw new Exception\MethodDisabledException('router is error, uri: ' . $uri);
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
            $result = $this->event->dispatchWithReturn(new Event\Pipeline($req, $res, $router, $event->getTraceId()));
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
}
