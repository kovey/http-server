<?php
/**
 *
 * @description 整个运用启动前的初始化
 *
 * @package     App\Bootstrap
 *
 * @time        Tue Sep 24 09:00:10 2019
 *
 * @author      kovey
 */
namespace Kovey\Web\App\Bootstrap;

use Kovey\Library\Process;
use Kovey\Web\Process\ClearSession;
use Kovey\Library\Config\Manager;
use Kovey\Web\App\Application;
use Kovey\Web\Server\Server;
use Kovey\Library\Process\UserProcess;
use Kovey\Web\App\Http\Request\Request;
use Kovey\Web\App\Http\Request\RequestInterface;
use Kovey\Web\App\Http\Response\Response;
use Kovey\Web\App\Http\Response\ResponseInterface;
use Kovey\Library\Container\Container;
use Kovey\Web\Middleware\SessionStart;
use Kovey\Web\App\Http\Router\Routers;
use Kovey\Web\App\Http\Router\Router;
use Kovey\Web\App\Http\Router\Route;
use Kovey\Web\App\Http\Router\RouterInterface;
use Kovey\Web\App\Mvc\View\Sample;
use Kovey\Web\App\Mvc\Controller\ControllerInterface;
use Kovey\Web\App\Http\Pipeline\Pipeline;
use Kovey\Logger\Logger;
use Kovey\Logger\Db;
use Kovey\Logger\Monitor;

class Bootstrap
{
	/**
	 * @description 初始化日志目录
	 *
	 * @param Application $app
	 *
	 * @return null
	 */
	public function __initLogger(Application $app)
	{
		ko_change_process_name(Manager::get('server.server.name') . ' root');
		Logger::setLogPath(Manager::get('server.logger.info'), Manager::get('server.logger.exception'), Manager::get('server.logger.error'), Manager::get('server.logger.warning'));
        Logger::setCategory(Manager::get('server.server.name'));
		Monitor::setLogDir(Manager::get('server.logger.monitor'));
		Db::setLogDir(Manager::get('server.logger.db'));

		if (Manager::get('server.session.open') === 'On' && Manager::get('server.session.type') === 'file') {
			if (!is_dir(Manager::get('server.session.dir'))) {
				mkdir(Manager::get('server.session.dir'), 0777, true);
			}
		}
	}

	/**
	 * @description 初始化APP
	 *
	 * @param Application $app
	 *
	 * @return null
	 */
	public function __initApp(Application $app)
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
	 * @description 初始化事件
	 *
	 * @param Application $app
	 *
	 * @return null
	 */
	public function __initEvents(Application $app)
	{
		$app->on('request', function ($request) {
				return new Request($request);
			})
			->on('response', function () {
				return new Response();
			})
			->on('view', function (ControllerInterface $con, $template) {
				$con->setView(new Sample($con->getResponse(), $template));
			})
			->on('pipeline', function (RequestInterface $req, ResponseInterface $res, RouterInterface $router, string $traceId) use($app) {
				return (new Pipeline($app->getContainer()))
					->via('handle')
					->send($req, $res)
					->through(array_merge($app->getDefaultMiddlewares(), $router->getMiddlewares()))
					->then(function (RequestInterface $req, ResponseInterface $res) use ($router, $app, $traceId) {
						return $app->runAction($req, $res, $router, $traceId);
					});
			});
	}

	/**
	 * @description 初始化进程
	 *
	 * @param Application $app
	 *
	 * @return null
	 */
	public function __initProcess(Application $app)
	{
		$app->registerProcess('kovey_config', (new Process\Config())->setProcessName(Manager::get('server.server.name') . ' config'));
		if (Manager::get('server.session.open') === 'On' && Manager::get('server.session.type') === 'file') {
			$app->registerProcess('kovey_session', new ClearSession());
		}
	}

	/**
	 * @description 初始化弟自定义的Bootstrap
	 *
	 * @param Application $app
	 *
	 * @return null
	 */
	public function __initCustomBoot(Application $app)
	{
		$bootstrap = $app->getConfig()['boot'] ?? 'application/Bootstrap.php';
		$file = APPLICATION_PATH . '/' . $bootstrap;
		if (!is_file($file)) {
			return $this;
		}

		require_once $file;

		$app->registerCustomBootstrap(new \Bootstrap());
	}

	/**
	 * @description 初始化路由
	 *
	 * @param Application $app
	 *
	 * @return null
	 */
	public function __initRouters(Application $app)
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
}
