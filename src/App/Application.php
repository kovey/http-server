<?php
/**
 *
 * @description Application global
 *
 * @package     App
 *
 * @time        Tue Sep 24 00:28:03 2019
 *
 * @author      kovey
 */
namespace Kovey\Web\App;

use Kovey\Connection\Pool\PoolInterface;
use Kovey\Connection\AppInterface;
use Kovey\Web\App\Http\Request\RequestInterface;
use Kovey\Web\App\Http\Response\ResponseInterface;
use Kovey\Web\App\Http\Router\RouterInterface;
use Kovey\Web\App\Http\Router\RoutersInterface;
use Kovey\Web\App\Mvc\Controller\ControllerInterface;
use Kovey\Web\App\Mvc\View\ViewInterface;
use Kovey\Process\ProcessAbstract;
use Kovey\Container\ContainerInterface;
use Kovey\Pipeline\Middleware\MiddlewareInterface;
use Kovey\Web\App\Bootstrap\Autoload;
use Kovey\Web\Server\Server;
use Kovey\Process\UserProcess;
use Kovey\Logger\Logger;
use Kovey\Logger\Monitor;
use Kovey\Library\Exception\KoveyException;
use Kovey\Web\Server\ErrorTemplate;
use Kovey\Web\Event;
use Kovey\Event\Dispatch;
use Kovey\Event\Listener\Listener;
use Kovey\Event\Listener\ListenerProvider;

class Application implements AppInterface
{
    /**
     * @description 配置
     *
     * @var Array
     */
    private Array $config;

    /**
     * @description 服务器
     *
     * @var Server
     */
    private Server $server;

    /**
     * @description 路由
     *
     * @var RoutersInterface
     */
    private RoutersInterface $routers;

    /**
     * @description 插件
     *
     * @var Array
     */
    private Array $plugins;

    /**
     * @description 自动加载
     *
     * @var Autoload
     */
    private Autoload $autoload;

    /**
     * @description 连接池
     *
     * @var Array
     */
    private Array $pools;

    /**
     * @description 容器
     *
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * @description 默认中间件
     *
     * @var Array
     */
    private Array $defaultMiddlewares;

    /**
     * @description 用户进程管理
     *
     * @var UserProcess
     */
    private UserProcess $userProcess;

    /**
     * @description 对象实例
     *
     * @var Application
     */
    private static ?Application $instance = null;

    /**
     * @description 全局变量
     *
     * @var Array
     */
    private Array $globals;

    private Dispatch $dispatch;

    private ListenerProvider $provider;

    private static Array $events = array(
        'console' => Event\Console::class,
        'monitor' => Event\Monitor::class,
        'response' => Event\Response::class,
        'request' => Event\Request::class,
        'run_action' => Event\RunAction::class,
        'pipeline' => Event\Pipeline::class,
        'view' => Event\View::class
    );

    private Array $onEvents;

    /**
     * @description 获取对象实例
     *
     * @param Array $config
     *
     * @return Application
     */
    public static function getInstance(Array $config = array()) : Application
    {
        if (self::$instance == null) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    /**
     * @description 构造
     *
     * @param Array $config
     *
     * @return Application
     */
    private function __construct(Array $config)
    {
        $this->config = $config;
        $this->plugins = array();
        $this->pools = array();
        $this->defaultMiddlewares = array();
        $this->globals = array();
        $this->provider = new ListenerProvider();
        $this->dispatch = new Dispatch($this->provider);
        $this->onEvents = array();
    }

    private function __clone()
    {}

    /**
     * @description 注册全局变量
     *
     * @param string $name
     *
     * @param mixed $val
     *
     * @return Application
     */
    public function registerGlobal(string $name, $val) : Application
    {
        $this->globals[$name] = $val;
        return $this;
    }

    /**
     * @description 获取全局变量
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getGlobal(string $name)
    {
        return $this->globals[$name] ?? null;
    }

    /**
     * @description 注册自动加载
     *
     * @param Autoload $autoload
     *
     * @return Application
     */
    public function registerAutoload(Autoload $autoload) : Application
    {
        $this->autoload = $autoload;
        return $this;
    }

    /**
     * @description 注册中间件
     *
     * @param MiddlewareInterface $middleware
     *
     * @return Application
     */
    public function registerMiddleware(MiddlewareInterface $middleware) : Application
    {
        $this->defaultMiddlewares[] = $middleware;
        return $this;
    }

    /**
     * @description 获取默认的中间件
     *
     * @return Array
     */
    public function getDefaultMiddlewares() : Array
    {
        return $this->defaultMiddlewares;
    }

    /**
     * @description 注册路由
     *
     * @param RoutersInterface $routers
     *
     * @return Application
     */
    public function registerRouters(RoutersInterface $routers) : Application
    {
        $this->routers = $routers;
        return $this;
    }

    /**
     * @description 注册服务器
     *
     * @param Server $server
     *
     * @return Application
     */
    public function registerServer(Server $server) : Application
    {
        $this->server = $server;
        $this->server
            ->on('workflow', array($this, 'workflow'))
            ->on('init', array($this, 'initPool'))
            ->on('console', array($this, 'console'))
            ->on('monitor', array($this, 'monitor'));

        return $this;
    }

    /**
     * @description 处理console事件
     *
     * @param string $path
     *
     * @param string $method
     *
     * @param Array $args
     *
     * @param string $traceId
     *
     * @return null
     */
    public function console(Event\Console $event) : void
    {
        try {
            $this->dispatch->dispatch($event);
        } catch (\Exception $e) {
            Logger::writeExceptionLog(__LINE__, __FILE__, $e, $event->getTraceId());
        } catch (\Throwable $e) {
            Logger::writeExceptionLog(__LINE__, __FILE__, $e, $event->getTraceId());
        }
    }

    /**
     * @description 注册容器
     *
     * @param ContainerInterface $container
     *
     * @return Application
     */
    public function registerContainer(ContainerInterface $container) : Application
    {
        $this->container = $container;
        return $this;
    }

    /**
     * @description 检查配置
     *
     * @return Application
     *
     * @throws KoveyException
     */
    public function checkConfig() : Application
    {
        $fields = array(
            'controllers', 'views', 'boot', 'template'
        );

        foreach ($fields as $field) {
            if (!isset($this->config[$field])) {
                throw new KoveyException('config is error', 500);
            }
        }

        return $this;
    }

    /**
     * @description 工作流
     *
     * @param Swoole\Http\Request $request
     *
     * @param string $traceId
     *
     * @return Array
     */
    public function workflow(Event\Workflow $event) : Array
    {
        $req = $this->dispatch->dispatchWithReturn(new Event\Request($event->getRequest()));
        if (!$req instanceof RequestInterface) {
            Logger::writeErrorLog(__LINE__, __FILE__, 'request is not implements Kovey\Web\App\Http\Request\RequestInterface.', $event->getTraceId());
            return array();
        }
        $res = $this->dispatch->dispatchWithReturn(new Event\Response());
        if (!$res instanceof ResponseInterface) {
            Logger::writeErrorLog(__LINE__, __FILE__, 'request is not implements Kovey\Web\App\Http\Response\ResponseInterface.', $event->getTraceId());
            return array();
        }
        $uri = trim($req->getUri());
        $router = $this->routers->getRouter($uri, $req->getMethod());
        if ($router === null) {
            Logger::writeErrorLog(__LINE__, __FILE__, 'router is error, uri: ' . $uri, $event->getTraceId());
            $res->status('405');
            return $res->toArray();
        }

        $req->setController($router->getController())
            ->setAction($router->getAction());

        $result = null;
        try {
            $result = $this->dispatch->dispatchWithReturn(new Event\Pipeline($req, $res, $router, $event->getTraceId()));
            if ($result instanceof ResponseInterface) {
                $result = $result->toArray();
            }
        } catch (\Throwable $e) {
            Logger::writeExceptionLog(__LINE__, __FILE__, $e, $event->getTraceId());
            $result = array(
                'httpCode' => ErrorTemplate::HTTP_CODE_500,
                'header' => array(
                    'content-type' => 'text/html'
                ),
                'content' => ErrorTemplate::getContent(ErrorTemplate::HTTP_CODE_500),
                'cookie' => array(),
                'err' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            );
        }

        $result['class'] = $router->getController() . 'Controller';
        $result['method'] = $router->getAction() . 'Action';
        return $result;
    }

    /**
     * @description monitor
     *
     * @param Array $data
     *
     * @return null
     */
    public function monitor(Event\Monitor $event) : void
    {
        Monitor::write($event->getData());
        go (function ($event) {
            $this->dispatch->dispatch($event);
        }, $event);
    }

    /**
     * @description 事件监听
     *
     * @param string $type
     *
     * @param callable | Array $fun
     *
     * @return Application
     */
    public function on(string $type, callable | Array $fun) : Application
    {
        if (!isset(self::$events[$type])) {
            return $this;
        }

        if (!is_callable($fun)) {
            return $this;
        }

        $listener = new Listener();
        $listener->addEvent(self::$events[$type], $fun);
        $this->provider->addListener($listener);
        $this->onEvents[$type] = 1;

        return $this;
    }

    /**
     * @description 运用启动
     *
     * @return null
     */
    public function run()
    {
        if (!is_object($this->server)) {
            throw new KoveyException('server not register');
        }

        $this->server->start();
    }

    /**
     * @description 注册启动类
     *
     * @param mixed $bootstrap
     *
     * @return Application
     */
    public function registerBootstrap($bootstrap) : Application
    {
        $this->bootstrap = $bootstrap;
        return $this;
    }

    /**
     * @description 注册自定义启动
     *
     * @param mixed $bootstrap
     *
     * @return Application
     */
    public function registerCustomBootstrap($bootstrap) : Application
    {
        $this->customBootstrap = $bootstrap;
        return $this;
    }

    /**
     * @description 启动前初始化
     *
     * @return Application
     */
    public function bootstrap() : Application
    {
        $btfuns = get_class_methods($this->bootstrap);
        foreach ($btfuns as $fun) {
            if (substr($fun, 0, 6) !== '__init') {
                continue;
            }

            $this->bootstrap->$fun($this);
        }

        $funs = get_class_methods($this->customBootstrap);
        foreach ($funs as $fun) {
            if (substr($fun, 0, 6) !== '__init') {
                continue;
            }

            $this->customBootstrap->$fun($this);
        }

        return $this;
    }

    /**
     * @description 执行Action
     *
     * @param RequestInterface $req
     *
     * @param ResponseInterface $res
     *
     * @param RouterInterface $router
     *
     * @param string $traceId
     *
     * @return Array
     */
    public function runAction(RequestInterface $req, ResponseInterface $res, RouterInterface $router, string $traceId) : Array
    {
        if (!empty($router->getCallable())) {
            $res->setBody(call_user_func($router->getCallable(), $req, $res));
            $res->status(200);
            return $res->toArray();
        }

        $conFile = APPLICATION_PATH . '/' . $this->config['controllers'] . $router->getClassPath();

        if (!is_file($conFile)) {
            Logger::writeErrorLog(__LINE__, __FILE__, "file of " . $router->getController() . " is not exists, controller file \" $conFile\".", $traceId);
            $res->status(404);
            return $res->toArray();
        }

        $action = $router->getActionName();
        try {
            $objectExt = $this->container->getKeywords($router->getClassName(), $action);
        } catch (\ReflectionException $e) {
            Logger::writeExceptionLog(__LINE__, __FILE__, $e, $traceId);
            $res->status(404);
            return $res->toArray();
        }

        $template = APPLICATION_PATH . '/' . $this->config['views'] . '/' . $router->getViewPath() . '.' . $this->config['template'];
        $obj = $this->container->get($router->getClassName(), $traceId, $objectExt['ext'], $req, $res, $this->plugins);
        if (!$obj instanceof ControllerInterface) {
            Logger::writeErrorLog(__LINE__, __FILE__, "class \"$controller\" is not extends Kovey\Web\App\Mvc\Controller\ControllerInterface.", $traceId);
            $res->status(404);
            return $res->toArray();
        }

        $httpCode = $obj->getResponse()->getHttpCode();
        if ($httpCode == 201 
            || ($httpCode > 300 && $httpCode < 400)
        ) {
            return $obj->getResponse()->toArray();
        }

        if (!$obj->isViewDisabled()) {
            $this->dispatch->dispatch(new Event\View($obj, $template));
        }

        $content = '';

        if ($objectExt['openTransaction']) {
            $objectExt['database']->getConnection()->beginTransaction();
            try {
                if (isset($this->onEvents['run_action'])) {
                    $content = $this->dispatch->dispatchWithReturn(new Event\RunAction($obj, $action, $this->container->getMethodArguments($router->getClassName(), $action, $traceId)));
                } else {
                    $content = call_user_func(array($obj, $action), ...$this->container->getMethodArguments($router->getClassName(), $action, $traceId));
                }
                $objectExt['database']->getConnection()->commit();
            } catch (\Throwable $e) {
                $objectExt['database']->getConnection()->rollBack();
                throw $e;
            }
        } else {
            if (isset($this->onEvents['run_action'])) {
                $content = $this->dispatch->dispatchWithReturn(new Event\RunAction($obj, $action, $this->container->getMethodArguments($router->getClassName(), $action, $traceId)));
            } else {
                $content = call_user_func(array($obj, $action), ...$this->container->getMethodArguments($router->getClassName(), $action, $traceId));
            }
        }


        if ($obj->isViewDisabled()) {
            $res->setBody($content);
            $res->status(200);
            return $res->toArray();
        }

        $httpCode = $obj->getResponse()->getHttpCode();
        if ($httpCode == 201 
            || ($httpCode > 300 && $httpCode < 400)
        ) {
            return $obj->getResponse()->toArray();
        }

        if (!is_file($template)) {
            Logger::writeErrorLog(__LINE__, __FILE__, "template \"$template\" is not exists.", $traceId);
            $res->status(404);
            $res->setBody('');
            return $res->toArray();
        }

        $obj->render();
        $res = $obj->getResponse();

        if (!$obj->isPluginDisabled()) {
            foreach ($obj->getPlugins() as $plugin) {
                $plugin->loopShutdown($req, $res);
            }
        }

        $res->status(200);
        return $res->toArray();
    }

    /**
     * @description 获取配置
     *
     * @return Array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @description 获取服务器
     *
     * @return Server
     */
    public function getServer() : Server
    {
        return $this->server;
    }

    /**
     * @description 注册GET路由
     *
     * @param string $uri
     *
     * @param RouterInterface $router
     *
     * @return Application
     */
    public function registerGetRouter(string $uri, RouterInterface $router) : Application
    {
        $this->routers->get($uri, $router);
        return $this;
    }

    /**
     * @description 注册POST路由
     *
     * @param string $uri
     *
     * @param RouterInterface $router
     *
     * @return Application
     */
    public function registerPostRouter(string $uri, RouterInterface $router) : Application
    {
        $this->routers->post($uri, $router);
        return $this;
    }

    /**
     * @description 注册PUT路由
     *
     * @param string $uri
     *
     * @param RouterInterface $router
     *
     * @return Application
     */
    public function registerPutRouter(string $uri, RouterInterface $router) : Application
    {
        $this->routers->put($uri, $router);
        return $this;
    }

    /**
     * @description 注册DEL路由
     *
     * @param string $uri
     *
     * @param RouterInterface $router
     *
     * @return Application
     */
    public function registerDelRouter(string $uri, RouterInterface $router) : Application
    {
        $this->routers->delete($uri, $router);
        return $this;
    }

    /**
     * @description 注册插件
     *
     * @param string $plugin
     *
     * @return Application
     */
    public function registerPlugin(string $plugin) : Application
    {
        $this->plugins[$plugin] = $plugin;
        return $this;
    }

    /**
     * @description 初始化连接池
     *
     * @param Server $serv
     *
     * @return null
     */
    public function initPool(Event\Init $event)
    {
        try {
            foreach ($this->pools as $pool) {
                if (is_array($pool)) {
                    foreach ($pool as $pl) {
                        $pl->init();
                        if (count($pl->getErrors()) > 0) {
                            Logger::writeErrorLog(__LINE__, __FILE__, implode(';', $pl->getErrors()));
                        }
                    }
                    continue;
                }

                $pool->init();
                if (count($pool->getErrors()) > 0) {
                    Logger::writeErrorLog(__LINE__, __FILE__, implode(';', $pool->getErrors()));
                }
            }
        } catch (\Exception $e) {
            Logger::writeExceptionLog(__LINE__, __FILE__, $e);
        } catch (\Throwable $e) {
            Logger::writeExceptionLog(__LINE__, __FILE__, $e);
        }
    }

    /**
     * @description 获取用户进程管理
     *
     * @return UserProcess
     */
    public function getUserProcess() : UserProcess
    {
        return $this->userProcess;
    }

    /**
     * @description 注册进程
     *
     * @param string $name
     *
     * @param ProcessAbstract $process
     *
     * @return Application
     */
    public function registerProcess(string $name, ProcessAbstract $process) : Application
    {
        if (!is_object($this->server)) {
            return $this;
        }

        $process->setServer($this->server->getServ());
        $this->userProcess->addProcess($name, $process);
        return $this;
    }

    /**
     * @description 注册本地类库
     *
     * @param string $path
     *
     * @return Application
     */
    public function registerLocalLibPath(string $path) : Application
    {
        $this->autoload->addLocalPath($path);
        return $this;
    }

    /**
     * @description 注册连接池
     *
     * @param string $name
     *
     * @param PoolInterface $pool
     *
     * @param int $partition
     *
     * @return Application
     */
    public function registerPool(string $name, PoolInterface $pool, int $partition = 0) : AppInterface
    {
        $this->pools[$name] ??= array();
        $this->pools[$name][$partition] = $pool;
        return $this;
    }

    /**
     * @description 获取连接池
     *
     * @param string $name
     *
     * @param int $partition
     *
     * @return PoolInterface | null
     */
    public function getPool(string $name, int $partition = 0) : ?PoolInterface
    {
        return $this->pools[$name][$partition] ?? null;
    }

    /**
     * @description 获取容器
     *
     *
     * @return ControllerInterface
     */
    public function getContainer() : ContainerInterface
    {
        return $this->container;
    }

    /**
     * @description 注册进程管理
     *
     * @param UserProcess $userProcess
     *
     * @return Application
     */
    public function registerUserProcess(UserProcess $userProcess) : Application
    {
        $this->userProcess = $userProcess;
        return $this;
    }

    /**
     * @description disable default router
     *
     * @return Application
     */
    public function disableDefaultRouter() : Application
    {
        if (!$this->routers instanceof RoutersInterface) {
            return $this;
        }

        $this->routers->disableDefault();
        return $this;
    }
}
