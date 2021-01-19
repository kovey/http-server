<?php
/**
 *
 * @description plugin interface
 *
 * @package     App\Http\Plugin
 *
 * @time        Tue Sep 24 08:59:02 2019
 *
 * @author      kovey
 */
namespace Kovey\Web\App\Http\Plugin;

use Kovey\Web\App\Http\Request\RequestInterface;
use Kovey\Web\App\Http\Response\ResponseInterface;

interface PluginInterface
{
    /**
     * @description workflow run end
     *
     * @param RequestInterface $request
     *
     * @param ResponseInterface $response
     *
     * @return void
     */
    public function loopShutdown(RequestInterface $request, ResponseInterface $response) : void;
}
