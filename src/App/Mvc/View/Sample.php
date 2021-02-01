<?php
/**
 *
 * @description sample view
 *
 * @package     App\Mvc
 *
 * @time        Tue Sep 24 08:55:24 2019
 *
 * @author      kovey
 */
namespace Kovey\Web\App\Mvc\View;

use Kovey\Web\App\Http\Response\ResponseInterface;
use Kovey\Web\App\Mvc\View\ViewInterface;

class Sample implements ViewInterface
{
    /**
     * @description template path
     *
     * @var string
     */
    private string $template;

    /**
     * @description response
     *
     * @var ResponseInterface
     */
    private ResponseInterface $res;

    /**
     * @description view data
     *
     * @var Array
     */
    private Array $data;

    /**
     * @description construct
     *
     * @param ResponseInterface $res
     *
     * @param string $template
     *
     * @return ViewInterface
     */
    final public function __construct(ResponseInterface $res, string $template)
    {
        $this->res = $res;
        $this->template = $template;
        $this->data = array();
    }

    /**
     * @description set template
     *
     * @param string $template
     *
     * @return ViewInterface
     */
    public function setTemplate(string $template) : ViewInterface
    {
        $this->template = $template;
        return $this;
    }

    /**
     * @description set veriable
     *
     * @param string $name
     *
     * @param mixed $val
     *
     * @return void
     */
    public function __set(string $name, mixed $val) : void
    {
        $this->data[$name] = $val;
    }

    /**
     * @description get veriable
     *
     * @param string $name
     *
     * @return mixed
     */
    public function __get(string $name) : mixed
    {
        return $this->data[$name] ?? null;
    }

    /**
     * @description render view
     *
     * @return ViewInterface
     */
    public function render() : ViewInterface
    {
        ob_start();
        ob_implicit_flush(0);
        extract($this->data);
        require($this->template);
        $content = ob_get_clean();
        $this->res->setBody($content);
        return $this;
    }

    /**
     * @description get response
     *
     * @return ResponseInterface
     */
    public function getResponse() : ResponseInterface
    {
        return $this->res;
    }
}
