<?php
/**
 *
 * @description bootstrap when app start
 *
 * @package     App\Bootstrap
 *
 * @time        Tue Sep 24 09:00:10 2019
 *
 * @author      kovey
 */
namespace Kovey\Web\App\Bootstrap;

use Kovey\Process\Process;
use Kovey\Web\Process\ClearSession;
use Kovey\Library\Config\Manager;
use Kovey\Web\App\Application;
use Kovey\Web\Server\Server;
use Kovey\Process\UserProcess;
use Kovey\Web\App\Http\Request\Request;
use Kovey\Web\App\Http\Request\RequestInterface;
use Kovey\Web\App\Http\Response\Response;
use Kovey\Web\App\Http\Response\ResponseInterface;
use Kovey\Container\Container;
use Kovey\Container\Event;
use Kovey\Web\Middleware\SessionStart;
use Kovey\Web\Middleware\Validator;
use Kovey\Web\App\Http\Router\Routers;
use Kovey\Web\App\Http\Router\Router;
use Kovey\Web\App\Http\Router\Route;
use Kovey\Web\App\Http\Router\RouterInterface;
use Kovey\Web\App\Mvc\View\Sample;
use Kovey\Web\App\Mvc\Controller\ControllerInterface;
use Kovey\Pipeline\Pipeline;
use Kovey\Logger\Logger;
use Kovey\Logger\Db;
use Kovey\Logger\Monitor;
use Kovey\Web\Event as WE;

class Bootstrap
{
    /**
     * @description init logger
     *
     * @param Application $app
     *
     * @return void
     */
    public function __initLogger(Application $app) : void
    {
        ko_change_process_name(Manager::get('server.server.name') . ' root');
        Logger::setLogPath(Manager::get('server.server.logger_dir'));
        Logger::setCategory(Manager::get('server.server.name'));
        Monitor::setLogDir(Manager::get('server.server.logger_dir'));
        Db::setLogDir(Manager::get('server.server.logger_dir'));

        if (Manager::get('server.session.open') === 'On' && Manager::get('server.session.type') === 'file') {
            if (!is_dir(Manager::get('server.session.dir'))) {
                mkdir(Manager::get('server.session.dir'), 0777, true);
            }
        }
    }

    /**
     * @description init $app
     *
     * @param Application $app
     *
     * @return void
     */
    public function __initApp(Application $app) : void
    {
        $app->registerServer(new Server(Manager::get('server.server')))
            ->registerContainer(new Container())
            ->registerRouters(new Routers())
            ->registerUserProcess(new UserProcess(Manager::get('server.server.worker_num')));
        if (Manager::get('server.session.open') === 'On') {
            $app->registerMiddleware(new SessionStart());
        }
    }

    /**
     * @description init event
     *
     * @param Application $app
     *
     * @return void
     */
    public function __initEvents(Application $app) : void
    {
        $app->on('request', function (WE\Request $event) {
                return new Request($event->getRequest());
            })
            ->on('response', function (WE\Response $event) {
                return new Response();
            })
            ->on('view', function (WE\View $event) {
                $event->getController()->setView(new Sample($event->getController()->getResponse(), $event->getTemplate()));
            })
            ->on('pipeline', function (WE\Pipeline $event) use($app) {
                return (new Pipeline($app->getContainer()))
                    ->via('handle')
                    ->send($event)
                    ->through(array_merge($app->getDefaultMiddlewares(), $event->getRouter()->getMiddlewares()))
                    ->then(function (WE\Pipeline $event) use ($app) {
                        return $app->runAction($event);
                    });
            });
    }

    /**
     * @description init process
     *
     * @param Application $app
     *
     * @return void
     */
    public function __initProcess(Application $app) : void
    {
        $app->registerProcess('kovey_config', (new Process\Config())->setProcessName(Manager::get('server.server.name') . ' config'));
        if (Manager::get('server.session.open') === 'On' && Manager::get('server.session.type') === 'file') {
            $app->registerProcess('kovey_session', new ClearSession());
        }
    }

    /**
     * @description init router
     *
     * @param Application $app
     *
     * @return void
     */
    public function __initRouters(Application $app) : void
    {
        $path = APPLICATION_PATH . '/' . $app->getConfig()['routers'];
        if (!is_dir($path)) {
            return;
        }

        Route::setApp($app);

        $files = scandir($path);
        foreach ($files as $file) {
            if (substr($file, -3) !== 'php') {
                continue;
            }

            require_once($path . '/' . $file);
        }
    }

    /**
     * @description init parse inject
     *
     * @param Application $app
     *
     * @return void
     */
    public function __initParseInject(Application $app) : void
    {
        $app->getContainer()
            ->on('Router', function (Event\Router $event) use ($app) {
                $router = new Router($event->getPath(), $event->getMethod(), $event->getRouter(), $event->getTemplate(), $event->getLayout(), $event->getLayoutDir());
                if (!$router->isValid()) {
                    return;
                }

                if (!empty($event->getRules())) {
                    $validator = new Validator();
                    $router->addMiddleware($validator->setRules($event->getRules()));
                }

                $app->registerRouter($event->getPath(), $router);
            })
            ->parse(APPLICATION_PATH . '/' . $app->getConfig()['controllers'], '', 'Controller');
    }
}
