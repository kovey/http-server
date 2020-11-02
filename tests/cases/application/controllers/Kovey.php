<?php
/**
 * @description
 *
 * @package
 *
 * @author kovey
 *
 * @time 2020-11-02 16:31:10
 *
 */
use Kovey\Web\App\Mvc\Controller\Controller;

class KoveyController extends Controller
{
    public function testAction()
    {
        $this->view->kovey = $this->getRequest()->getQuery('kovey');
        $this->view->http = $this->getRequest()->getQuery('http');
    }
}
