<?php
/**
 * @description
 *
 * @package
 *
 * @author kovey
 *
 * @time 2021-04-07 13:22:40
 *
 */
namespace Kovey\Web\App\Bootstrap;

use Kovey\Library\Config\Manager;
use Kovey\Web\App\Application;
use Kovey\Web\Event;
use Kovey\Web\App\Http\Request\Request;
use Kovey\Web\App\Http\Response\Response;
use Kovey\Web\App\Mvc\View\Sample;
use Kovey\Pipeline\Pipeline;

class EventInit
{
    public function __initEvents(Application $app) : void
    {
        $app->on('request', function (Event\Request $event) {
                return new Request($event->getRequest());
            })
            ->on('response', function (Event\Response $event) {
                return new Response();
            })
            ->on('view', function (Event\View $event) {
                $event->getController()->setView(new Sample($event->getController()->getResponse(), $event->getTemplate()));
            })
            ->on('pipeline', function (Event\Pipeline $event) use($app) {
                return (new Pipeline($app->getContainer()))
                    ->via('handle')
                    ->send($event)
                    ->through(array_merge($app->getDefaultMiddlewares(), $event->getRouter()->getMiddlewares()))
                    ->then(function (Event\Pipeline $event) use ($app) {
                        return $app->runAction($event);
                    });
            });
    }
}
