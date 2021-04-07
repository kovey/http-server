<?php
/**
 * @description
 *
 * @package
 *
 * @author kovey
 *
 * @time 2021-04-07 13:17:19
 *
 */
namespace Kovey\Web\App\Bootstrap;

use Kovey\Library\Config\Manager;
use Kovey\Web\App\Application;
use Kovey\Web\Server\Server;
use Kovey\Web\App\Http\Router\Routers;
use Kovey\Web\Middleware\SessionStart;

class BaseInit
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
            ->registerRouters(new Routers());

        if (Manager::get('server.session.open') === 'On') {
            $app->registerMiddleware(new SessionStart());
        }
    }
}
