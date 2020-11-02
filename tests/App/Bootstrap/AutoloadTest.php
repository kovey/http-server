<?php
/**
 * @description
 *
 * @package
 *
 * @author kovey
 *
 * @time 2020-10-30 13:31:30
 *
 */
namespace Kovey\Web\App\Bootstrap;

use PHPUnit\Framework\TestCase;

class AutoloadTest extends TestCase
{
    public function testAutoload()
    {
        $autoload = new Autoload();
        $autoload->register();
        $this->assertInstanceOf(Autoload::class, $autoload->addLocalPath(APPLICATION_PATH . '/src'));
    }

    public function testAutoloadController()
    {
        $autoload = new Autoload();
        $autoload->autoloadController('TestController');
        $test = new \TestController();
        $this->assertEquals('test', $test->indexAction());
    }

    public function testAutoloadPlugins()
    {
        $autoload = new Autoload();
        $autoload->autoloadPlugins('TestPlugin');
        $plugin = new \TestPlugin();
        $this->assertEquals('test', $plugin->getTest());
    }

    public function testAutoloadLib()
    {
        $autoload = new Autoload();
        $autoload->autoloadUserLib('Test\\Kovey');
        $kovey = new \Test\Kovey();
        $this->assertEquals('kovey', $kovey->getName());
    }

    public function testAutoloadLoal()
    {
        $autoload = new Autoload();
        $autoload->addLocalPath(APPLICATION_PATH . '/lib');
        $autoload->autoloadLocal('Test\\Local');
        $local = new \Test\Local();
        $this->assertEquals('local', $local->getName());
    }
}
