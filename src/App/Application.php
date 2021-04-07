<?php
/**
 * @description
 *
 * @package
 *
 * @author kovey
 *
 * @time 2021-02-02 13:13:43
 *
 */
namespace Kovey\Web\App;

use Kovey\App\App;
use Kovey\Web\Event;
use Kovey\Library\Exception\KoveyException;
use Kovey\App\Components\ServerInterface;
use Kovey\Web\App\Components\WorkPipe;
use Kovey\Pipeline\Middleware\MiddlewareInterface;
use Kovey\Web\App\Http\Router\RoutersInterface;
use Kovey\Web\App\Http\Router\RouterInterface;
use Kovey\Web\App\Bootstrap;

class Application extends App
{
    /**
     * @description Application instance
     *
     * @var Application
     */
    private static ?Application $instance = null;

    /**
     * @description get instance
     *
     * @param Array $config
     *
     * @return Application
     */
    public static function getInstance(Array $config = array()) : Application
    {
        if (empty(self::$instance)) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    /**
     * @description init
     *
     * @return Application
     */
    protected function init() : Application
    {
        $this->event->addSupportEvents(array(
            'response' => Event\Response::class,
            'request' => Event\Request::class,
            'run_action' => Event\RunAction::class,
            'pipeline' => Event\Pipeline::class,
            'view' => Event\View::class
        ));

        $this->bootstrap
             ->add(new Bootstrap\BaseInit())
             ->add(new Bootstrap\EventInit())
             ->add(new Bootstrap\ProcessInit())
             ->add(new Bootstrap\RouterInit());

        return $this;
    }

    protected function initWork() : Application
    {
        $this->work = new WorkPipe($this->config['controllers'] ?? '', $this->config['views'] ?? '', $this->config['template'] ?? '');
        $this->work->setEventManager($this->event);
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
        $this->work->addMiddleware($middleware);
        return $this;
    }

    /**
     * @description get default middlewares
     *
     * @return Array
     */
    public function getDefaultMiddlewares() : Array
    {
        return $this->work->getMiddlewares();
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
        $this->work->setRouters($routers);
        return $this;
    }

    /**
     * @description register server
     *
     * @param Server $server
     *
     * @return Application
     */
    public function registerServer(ServerInterface $server) : Application
    {
        $this->server = $server;
        $this->server
            ->on('workflow', array($this->work, 'run'))
            ->on('init', array($this->pools, 'initPool'))
            ->on('console', array($this, 'console'))
            ->on('monitor', array($this, 'monitor'));

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
     * @description run action
     *
     * @param Event\Pipeline $event
     *
     * @return Array
     */
    public function runAction(Event\Pipeline $event) : Array
    {
        return $this->work->runWorkPipe($event);
    }

    /**
     * @description register router
     *
     * @param string $uri
     *
     * @param RouterInterface $router
     *
     * @return Application
     */
    public function registerRouter(string $uri, RouterInterface $router) : Application
    {
        $this->work->addRouter($uri, $router);
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
        $this->work->addPlugin($plugin);
        return $this;
    }

    /**
     * @description disable default router
     *
     * @return Application
     */
    public function disableDefaultRouter() : Application
    {
        $this->work->disableDefaultRouter();
        return $this;
    }

    public function workflow(Event\Workflow $event)
    {
        return $this->work->run($event);
    }
}
