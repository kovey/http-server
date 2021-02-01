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

    private function getControllerObject(RouterInterface $router, string $traceId, RequestInterface $req, ResponseInterface $res) : ControllerInterface
    {
        $conFile = APPLICATION_PATH . '/' . $this->controllerPath . $router->getClassPath();
        if (!is_file($conFile)) {
            throw new Exception\PageNotFoundExcpetion("file of " . $router->getController() . " is not exists, controller file \" $conFile\".");
        }

        $action = $router->getActionName();
        try {
            $objectExt = $this->container->getKeywords($router->getClassName(), $action);
        } catch (\ReflectionException $e) {
            throw new Exception\PageNotFoundExcpetion($e->getMessage());
        }

        $obj = $this->container->get($router->getClassName(), $traceId, $objectExt['ext'], $req, $res, $this->plugins);
        if (!$obj instanceof ControllerInterface) {
            throw new Exception\PageNotFoundExcpetion("class " . $router->getClassName() . " is not extends Kovey\Web\App\Mvc\Controller\ControllerInterface.");
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
        if (!is_file($template)) {
            throw new PageNotFoundExcpetion("template \"$template\" is not exists.");
        }

        $this->dispatch->dispatch(new Event\View($obj, $template));
    }

    private function triggerAction(ControllerInterface $obj, RouterInterface $router, string $traceId, bool $hasRunAction) : string
    {
        if ($hasRunAction) {
            return $this->dispatch->dispatchWithReturn(new Event\RunAction(
                $obj, $router->getAction(), $this->container->getMethodArguments($router->getClassName(), $router->getAction(), $traceId)
            ));
        }

        return call_user_func(array($obj, $router->getAction()), ...$this->container->getMethodArguments($router->getClassName(), $router->getAction(), $traceId));
    }

    private function getContent(ControllerInterface $obj, RouterInterface $router, string $traceId, bool $hasRunAction) : ?string
    {
        if (!$obj->isOpenTransaction()) {
            return $this->triggerAction($obj, $router, $traceId, $hasRunAction);
        }

        $obj->database->beginTransaction();
        try {
            $content = $this->triggerAction($obj, $router, $traceId, $hasRunAction);
            $obj->database->commit();
        } catch (\Throwable $e) {
            $obj->database->rollBack();
            throw $e;
        }

        return $content;
    }

    private function viewRender(ControllerInterface $obj, RequestInterface $req, ResponseInterface $res, ?string $content) : Array
    {
        if ($obj->isViewDisabled()) {
            $res->setBody($content);
            $res->status(200);
            return $res->toArray();
        }

        if ($this->checkRedirect($obj)) {
            return $obj->getResponse()->toArray();
        }

        $obj->render();

        if (!$obj->isPluginDisabled()) {
            foreach ($obj->getPlugins() as $plugin) {
                $plugin->loopShutdown($req, $res);
            }
        }

        $res->status(200);
        return $res->toArray();
    }

    private function callRouter(RouterInterface $router, RequestInterface $req, ResponseInterface $res) : Array
    {
        $res->setBody(call_user_func($router->getCallable(), $req, $res));
        $res->status(200);
        return $res->toArray();
    }

    public function runWorkPipe(RequestInterface $req, ResponseInterface $res, RouterInterface $router, string $traceId, bool $hasRunAction) : Array
    {
        if (!empty($router->getCallable())) {
            return $this->callRouter($router, $req, $res);
        }

        $obj = $this->getControllerObject($router, $traceId, $req, $res);
        if ($this->checkRedirect($obj)) {
            return $obj->getResponse()->toArray();
        }

        $this->initView($obj, $router);

        return $this->viewRender($obj, $req, $res, $this->getContent($obj, $router, $traceId, $hasRunAction));
    }
}
