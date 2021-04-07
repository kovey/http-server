<?php
/**
 * @description
 *
 * @package
 *
 * @author kovey
 *
 * @time 2021-04-07 13:29:05
 *
 */
namespace Kovey\Web\App\Bootstrap;

use Kovey\Library\Config\Manager;
use Kovey\Web\App\Application;
use Kovey\Web\Process\ClearSession;

class ProcessInit
{
    /**
     * @description init process
     *
     * @param Application $app
     *
     * @return void
     */
    public function __initProcess(Application $app) : void
    {
        if (Manager::get('server.session.open') === 'On' && Manager::get('server.session.type') === 'file') {
            $app->registerProcess('kovey_session', new ClearSession());
        }
    }
}
