<?php
/**
 * @description
 *
 * @package
 *
 * @author kovey
 *
 * @time 2020-10-30 16:50:01
 *
 */
namespace Kovey\Web\App\Http\Session;

use PHPUnit\Framework\TestCase;
use Swoole\Coroutine\System;

class FileTest extends TestCase
{
    private static $dir;

    private static $sessionId;

    public static function setUpBeforeClass() : void
    {
        self::$dir = APPLICATION_PATH . '/session';
        if (!is_dir(self::$dir)) {
            mkdir(self::$dir, 0777, true);
        }

        $session = new File(self::$dir, '');
        $session->name = 'kovey';
        $session->password = 'password';
        self::$sessionId = $session->getSessionId();
    }

    public function testSession()
    {
        $session = new File(self::$dir, '');
        $session->name = 'kovey';
        $session->password = 'password';
        $this->assertEquals('kovey', $session->name);
        $this->assertEquals('password', $session->password);
        $sessionId = $session->getSessionId();
        $this->assertTrue(!empty($sessionId));
    }

    public function testSessionDel()
    {
        $session = new File(self::$dir, self::$sessionId);
        $session->del('name');
        $this->assertEquals(null, $session->name);
        $this->assertEquals('password', $session->password);
    }

    public function testSessionClear()
    {
        $session = new File(self::$dir, self::$sessionId);
        $this->assertEquals(null, $session->name);
        $session->clear();
        $this->assertEquals(null, $session->name);
        $this->assertEquals(null, $session->password);
    }

    public static function tearDownAfterClass() : void
    {
        System::sleep(0.1);

        if (!is_dir(self::$dir)) {
            return;
        }

        foreach (scandir(self::$dir) as $file) {
            if (substr($file, 0, 1) == '.') {
                continue;
            }

            unlink(self::$dir . '/' . $file);
        }

        rmdir(self::$dir);
    }
}
