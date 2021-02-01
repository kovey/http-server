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
use Kovey\Web\Exception;

class Application implements AppInterface
{
    /**
     * @description config
     *
     * @var Array
     */
    private Array $config;

    /**
     * @description server
     *
     * @var Server
     */
    private Server $server;

    /**
     * @description routers
     *
     * @var RoutersInterface
     */
    private RoutersInterface $routers;

    /**
     * @description autoload
     *
     * @var Autoload
     */
    private Autoload $autoload;

    /**
     * @description pools
     *
     * @var Array
     */
    private Array $pools;

    /**
     * @description container
     *
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * @description default middleware
     *
     * @var Array
     */
    private Array $defaultMiddlewares;

    /**
     * @description user process
     *
     * @var UserProcess
     */
    private UserProcess $userProcess;

    /**
     * @description Application instance
     *
     * @var Application
     */
    private static ?Application $instance = null;

    /**
     * @description global
     *
     * @var Array
     */
    private Array $globals;

    /**
     * @description event dispatcher
     *
     * @var Dispatch
     */
    private Dispatch $dispatch;

    /**
     * @description event listener provider
     *
     * @var ListenerProvider
     */
    private ListenerProvider $provider;

    /**
     * @description event supports
     *
     * @var Array
     */
    private static Array $events = array(
        'console' => Event\Console::class,
        'monitor' => Event\Monitor::class,
        'response' => Event\Response::class,
        'request' => Event\Request::class,
        'run_action' => Event\RunAction::class,
        'pipeline' => Event\Pipeline::class,
        'view' => Event\View::class
    );

    /**
     * @description events listened
     *
     * @var Array
     */
    private Array $onEvents;

    private WorkPipe $workPipe;

    /**
     * @description get instance
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
     * @description constructor
     *
     * @param Array $config
     *
     * @return Application
     */
    private function __construct(Array $config)
    {
        $this->config = $config;
        $this->pools = array();
        $this->defaultMiddlewares = array();
        $this->globals = array();
        $this->provider = new ListenerProvider();
        $this->dispatch = new Dispatch($this->provider);
        $this->onEvents = array();
        $this->workPipe = new WorkPipe($config['controllers'] ?? '', $config['views'] ?? '', $config['template'] ?? '');
        $this->workPipe->setDispatch($this->dispatch);
    }

    private function __clone()
    {}

    /**
     * @description register global
     *
     * @param string $name
     *
     * @param mixed $val
     *
     * @return Application
     */
    public function registerGlobal(string $name, mixed $val) : Application
    {
        $this->globals[$name] = $val;
        return $this;
    }

    /**
     * @description get global
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getGlobal(string $name) : mixed
    {
        return $this->globals[$name] ?? null;
    }

    /**
     * @description register autoload
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
     * @description register middleware
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
     * @description get default middlewares
     *
     * @return Array
     */
    public function getDefaultMiddlewares() : Array
    {
        return $this->defaultMiddlewares;
    }

    /**
     * @description register routers
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
     * @description register server
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
     * @description console event
     *
     * @param Event\Console $event
     *
     * @return void
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
     * @description register container
     *
     * @param ContainerInterface $container
     *
     * @return Application
     */
    public function registerContainer(ContainerInterface $container) : Application
    {
        $this->container = $container;
        $this->workPipe->setContainer($container);

        return $this;
    }

    /**
     * @description check config
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
     * @description work flow
     *
     * @param Event\Workflow $event
     *
     * @return Array
     */
    public function workflow(Event\Workflow $event) : Array
    {
        $req = $this->dispatch->dispatchWithReturn(new Event\Request($event->getRequest()));
        if (!$req instanceof RequestInterface) {
            throw new Exception\InternalException('request is not implements Kovey\Web\App\Http\Request\RequestInterface.');
        }

        $res = $this->dispatch->dispatchWithReturn(new Event\Response());
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
            $result = $this->dispatch->dispatchWithReturn(new Event\Pipeline($req, $res, $router, $event->getTraceId()));
            if ($result instanceof ResponseInterface) {
                $result = $result->toArray();
            }
        } catch (Exception\PageNotFoundException $e) {
            Logger::writeExceptionLog(__LINE__, __FILE__, $e, $traceId);
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

    /**
     * @description monitor
     *
     * @param Array $data
     *
     * @return void
     */
    public function monitor(Event\Monitor $event) : void
    {
        Monitor::write($event->getData());
        go (function ($event) {
            $this->dispatch->dispatch($event);
        }, $event);
    }

    /**
     * @description event listener
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
     * @description app run
     *
     * @return void
     */
    public function run() : void
    {
        if (!is_object($this->server)) {
            throw new KoveyException('server not register');
        }

        $this->server->start();
    }

    /**
     * @description register bootstrap
     *
     * @param mixed $bootstrap
     *
     * @return Application
     */
    public function registerBootstrap(mixed $bootstrap) : Application
    {
        $this->bootstrap = $bootstrap;
        return $this;
    }

    /**
     * @description register custom bootstrap
     *
     * @param mixed $bootstrap
     *
     * @return Application
     */
    public function registerCustomBootstrap(mixed $bootstrap) : Application
    {
        $this->customBootstrap = $bootstrap;
        return $this;
    }

    /**
     * @description run bootstrap
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
     * @description run action
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
        return $this->workPipe->runWorkPipe($req, $res, $router, $traceId, isset($this->onEvents['run_action']));
    }

    /**
     * @description get config
     *
     * @return Array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @description get server
     *
     * @return Server
     */
    public function getServer() : Server
    {
        return $this->server;
    }

    /**
     * @description register get router
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
     * @description register post router
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
     * @description register put router
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
     * @description register delete router
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
     * @description register plugin
     *
     * @param string $plugin
     *
     * @return Application
     */
    public function registerPlugin(string $plugin) : Application
    {
        $this->workPipe->addPlugin($plugin);
        return $this;
    }

    /**
     * @description init pool event
     *
     * @param Event\Init $event
     *
     * @return void
     */
    public function initPool(Event\Init $event) : void
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
     * @description get user process
     *
     * @return UserProcess
     */
    public function getUserProcess() : UserProcess
    {
        return $this->userProcess;
    }

    /**
     * @description register process
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
     * @description register local library path
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
     * @description register pool
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
     * @description get pool
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
     * @description get container
     *
     * @return ContainerInterface
     */
    public function getContainer() : ContainerInterface
    {
        return $this->container;
    }

    /**
     * @description register user process
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
