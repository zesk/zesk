<?php

declare(strict_types=1);

namespace test;

use zesk\Doctrine\Lock;
use zesk\Doctrine\ModelTestCase;
use zesk\Doctrine\Server;
use zesk\Doctrine\User;
use zesk\HTTP;

class LockTest extends ModelTestCase {
	public function test_Lock(): void {
		$lock = Lock::instance($this->application, __METHOD__);

		$this->assertInstanceOf(Lock::class, $lock);
		$this->assertFalse($lock->isLocked());
		$this->assertEquals(__METHOD__, $lock->code);

//		$thisServer = Server::singleton($this->application);
//
//		$this->assertEquals($thisServer, $lock->server);

		$this->assertFalse($lock->isLocked());
		$this->assertEquals($lock, $lock->acquire());
		$this->assertTrue($lock->isLocked());
	}
}
