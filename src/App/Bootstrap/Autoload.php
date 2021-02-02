<?php
/**
 *
 * @description 自动加载管理
 *
 * @package     App\Bootstrap
 *
 * @time        Tue Sep 24 08:59:43 2019
 *
 * @author      kovey
 */
namespace Kovey\Web\App\Bootstrap;

use Kovey\App\Components\AutoloadInterface;

class Autoload
{
    /**
     * @description 自定义加载目录
     *
     * @var Array
     */
    private Array $customs;

    /**
     * @description 控制器所在mulu
     *
     * @var string
     */
    private string $controllers;

    /**
     * @description 插件所在目录
     *
     * @var string
     */
    private string $plugins;

    /**
     * @description 库所在目录
     *
     * @var string
     */
    private string $library;

    /**
     * @description 构造
     *
     * @return Autoload
     */
    public function __construct()
    {
        $this->controllers = APPLICATION_PATH . '/application/controllers/';
        $this->plugins = APPLICATION_PATH . '/application/plugins/';
        $this->library = APPLICATION_PATH . '/application/library/';

        $this->customs = array();
    }

    /**
     * @description register
     *
     * @return void
     */
    public function register() : void
    {
        spl_autoload_register(array($this, 'autoloadController'));
        spl_autoload_register(array($this, 'autoloadPlugins'));
        spl_autoload_register(array($this, 'autoloadUserLib'));
        spl_autoload_register(array($this, 'autoloadLocal'));
    }

    /**
     * @description register controllers
     *
     * @param string $className
     *
     * @return void
     */
    function autoloadController(string $className) : void
    {
        try {
            $className = str_replace('Controller', '', $className);
            $className = $this->controllers . str_replace('\\', '/', $className) . '.php';
            $className = str_replace('//', '/', $className);
            if (!is_file($className)) {
                return;
            }
            require_once $className;
        } catch (\Throwable $e) {
            echo $e->getMessage();
        }
    }

    /**
     * @description register plugins
     * 
     * @param string
     *
     * @return void
     */
    function autoloadPlugins(string $className) : void
    {
        try {
            $className = $this->plugins . str_replace('\\', '/', $className) . '.php';
            $className = str_replace('//', '/', $className);
            if (!is_file($className)) {
                return;
            }

            require_once $className;
        } catch (\Throwable $e) {    
            echo $e->getMessage();
        }
    }

    /**
     * @description autoload user library
     *
     * @param string $className
     *
     * @return void
     */
    function autoloadUserLib(string $className) : void
    {
        try {
            $className = $this->library . str_replace('\\', '/', $className) . '.php';
            $className = str_replace('//', '/', $className);
            if (!is_file($className)) {
                return;
            }

            require_once $className;
        } catch (\Throwable $e) {    
            echo $e->getMessage();
        }
    }

    /**
     * @description autoload local path
     *
     * @param string $className
     *
     * @return void
     */
    public function autoloadLocal(string $className) : void
    {
        foreach ($this->customs as $path) {
            try {
                $className = $path . '/' . str_replace('\\', '/', $className) . '.php';
                if (!is_file($className)) {
                    continue;
                }

                require_once $className;
                break;
            } catch (\Throwable $e) {
                echo $e->getMessage();
            }
        }
    }

    /**
     * @description add local path
     *
     * @param string $path
     *
     * @return Autoload
     */
    public function addLocalPath(string $path) : AutoloadInterface
    {
        if (!is_dir($path)) {
            return $this;
        }
        $this->customs[] = $path;
        return $this;
    }
}
