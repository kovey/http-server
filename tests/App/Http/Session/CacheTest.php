<?php
/**
 * @description
 *
 * @package
 *
 * @author kovey
 *
 * @time 2020-10-30 17:38:00
 *
 */
namespace Kovey\Web\App\Http\Session;

use PHPUnit\Framework\TestCase;
use Kovey\Connection\Pool\Redis;
use Kovey\Connection\Pool;
use Kovey\Redis\Redis\Redis as RD;

class CacheTest extends TestCase
{
    private static $sessionId;

    public static function setUpBeforeClass() : void
    {
        $session = new Cache(new Pool(new Redis(array(
            'min' => 1,
            'max' => 2
        ), array(
            'host' => '127.0.0.1',
            'port' => 6379,
            'db' => 0
        ))), '');
        $session->name = 'kovey';
        $session->password = 'password';
        self::$sessionId = $session->getSessionId();
    }

    public function testSessionDel()
    {
        $session = new Cache(new Pool(new Redis(array(
            'min' => 1,
            'max' => 2
        ), array(
            'host' => '127.0.0.1',
            'port' => 6379,
            'db' => 0
        ))), self::$sessionId);
        $session->del('name');
        $this->assertEquals(null, $session->name);
        $this->assertEquals('password', $session->password);
    }

    public function testSessionClear()
    {
        $session = new Cache(new Pool(new Redis(array(
            'min' => 1,
            'max' => 2
        ), array(
            'host' => '127.0.0.1',
            'port' => 6379,
            'db' => 0
        ))), self::$sessionId);
        $this->assertEquals(null, $session->name);
        $session->clear();
        $this->assertEquals(null, $session->name);
        $this->assertEquals(null, $session->password);
    }

    public static function tearDownAfterClass() : void
    {
        $rd = new RD(array(
            'host' => '127.0.0.1',
            'port' => 6379,
            'db' => 0
        ));
        $rd->connect();
        $rd->del(Cache::SESSION_KEY . self::$sessionId);
    }
}
