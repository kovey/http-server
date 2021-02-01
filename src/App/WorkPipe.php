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
namespace Kovey\Web\App;

use Kovey\Event\Dispatch;
use Kovey\Web\Exception;
use Kovey\Web\Event;
use Kovey\Web\App\Http\Request\RequestInterface;
use Kovey\Web\App\Http\Response\ResponseInterface;
use Kovey\Web\App\Http\Router\RouterInterface;
use Kovey\Web\App\Mvc\Controller\ControllerInterface;
use Kovey\Container\ContainerInterface;

class WorkPipe
{
    private ContainerInterface $container;

    private string $controllerPath;

    private string $viewPath;

    private string $templateSuffix;

    private Dispatch $dispatch;

    private Array $plugins;

    public function __construct(string $controllerPath, string $viewPath, string $templateSuffix)
    {
        $this->controllerPath =  $controllerPath;
        $this->viewPath = $viewPath;
        $this->templateSuffix = $templateSuffix;
        $this->plugins = array();
    }

    public function setContainer(ContainerInterface $container) : WorkPipe
    {
        $this->container = $container;
        return $this;
    }

    public function setDispatch(Dispatch $dispatch) : WorkPipe
    {
        $this->dispatch = $dispatch;
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
        $this->dispatch->dispatch(new Event\View($obj, $template));
    }

    private function triggerAction(ControllerInterface $obj, Event\Pipeline $event, bool $hasRunAction) : ?string
    {
        $router = $event->getRouter();
        if ($hasRunAction) {
            return $this->dispatch->dispatchWithReturn(new Event\RunAction(
                $obj, $router->getActionName(), $this->container->getMethodArguments($router->getClassName(), $router->getActionName(), $event->getTraceId())
            ));
        }

        return call_user_func(array($obj, $router->getActionName()), ...$this->container->getMethodArguments($router->getClassName(), $router->getActionName(), $event->getTraceId()));
    }

    private function getContent(ControllerInterface $obj, Event\Pipeline $event, bool $hasRunAction) : ?string
    {
        if (!$obj->isOpenTransaction()) {
            return $this->triggerAction($obj, $event, $hasRunAction);
        }

        $obj->database->beginTransaction();
        try {
            $content = $this->triggerAction($obj, $event, $hasRunAction);
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

    public function runWorkPipe(Event\Pipeline $event, bool $hasRunAction) : Array
    {
        if (!empty($event->getRouter()->getCallable())) {
            return $this->callRouter($event);
        }

        $obj = $this->getControllerObject($event);
        if ($this->checkRedirect($obj)) {
            return $obj->getResponse()->toArray();
        }

        $this->initView($obj, $event->getRouter());

        return $this->viewRender($obj, $event, $this->getContent($obj, $event, $hasRunAction));
    }
}
