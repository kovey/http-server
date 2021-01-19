<?php
/**
 *
 * @description  View Interface
 *
 * @package     
 *
 * @time        Mon Sep 30 09:54:42 2019
 *
 * @author      kovey
 */
namespace Kovey\Web\App\Mvc\View;
use Kovey\Web\App\Http\Response\ResponseInterface;

interface ViewInterface
{
    /**
     * @description constructor
     *
     * @param ResponseInterface $res
     *
     * @param string $template
     *
     * @return ViewInterface
     */
    public function __construct(ResponseInterface $res, string $template);

    /**
     * @description set template
     *
     * @param string $template
     *
     * @return ViewInterface
     */
    public function setTemplate(string $template) : ViewInterface;

    /**
     * @description render view
     *
     * @return ViewInterface
     */
    public function render() : ViewInterface;

    /**
     * @description get response
     *
     * @return ResponseInterface
     */
    public function getResponse() : ResponseInterface;
}
