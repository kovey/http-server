<?php
/**
 * @description
 *
 * @package
 *
 * @author kovey
 *
 * @time 2021-04-07 13:40:02
 *
 */
namespace Kovey\Web\App\Bootstrap;

use Kovey\Library\Config\Manager;
use Kovey\Web\App\Application;
use Kovey\Web\Middleware\Validator;
use Kovey\Web\App\Http\Router\Router;
use Kovey\Web\App\Http\Router\Route;
use Kovey\Container\Event;

class RouterInit
{
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
    public function __initRouterInInject(Application $app) : void
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
