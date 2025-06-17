<?php

declare(strict_types=1);

namespace testZesk\Doctrine;

use Doctrine\ORM\Exception\ORMException;
use Throwable;
use zesk\Doctrine\Lock;
use zesk\Exception\LockedException;
use zesk\Exception\SemanticsException;
use zesk\Exception\TimeoutExpired;

/**
 * @coversDefaultClass \zesk\Doctrine\Lock
 */
class LockTest extends ModelTestCase {
	
	public array $entities = [Lock::class];
	
	/**
	 * @covers ::deleteDeadProcesses ::deleteUnused
	 * @covers ::instance ::isLocked ::isMine ::acquire ::crack ::getLockState
	 * @return void
	 * @throws LockedException
	 * @throws TimeoutExpired
	 * @throws ORMException
	 * @throws SemanticsException
	 */
	public function test_Lock(): void
	{
		Lock::deleteUnused($this->testApplication);
		Lock::deleteDeadProcesses($this->testApplication);
		
		$childName = __METHOD__ . '-child';
		$crackChildName = __METHOD__ . '-child-crack';
		$parentName = __METHOD__;
		$acquireWait = 2;
		
		$lock = Lock::instance($this->testApplication, $parentName);
		
		$this->assertFalse($lock->isLocked());
		$this->assertEquals($parentName, $lock->code);
		
		//		$thisServer = Server::singleton($this->application);
		//
		//		$this->assertEquals($thisServer, $lock->server);
		
		$this->assertFalse($lock->isLocked());
		$this->assertFalse($lock->isMine());
		$this->assertEquals($lock, $lock->acquire());
		$this->assertFalse($lock->isLocked());
		$this->assertTrue($lock->isMine());
		if (($secondPid = pcntl_fork()) === 0) {
			/* I am now the child */
			$lock->entityManager()->getConnection()->close();
			$childLock = Lock::instance($this->testApplication, $childName);
			$crackLock = Lock::instance($this->testApplication, $crackChildName);
			$parentLock = Lock::instance($this->testApplication, $parentName);
			
			$this->assertEquals($crackLock, $crackLock->acquire());
			
			$this->assertFalse($childLock->isMine());
			$this->assertFalse($parentLock->isMine());
			$this->assertFalse($childLock->isLocked());
			$this->assertTrue($parentLock->isLocked());
			$this->assertEquals($lock->code, $parentLock->code);
			$this->assertEquals($lock->id, $parentLock->id);
			
			try {
				$savePid = $parentLock->getLockState();
				$this->assertNull($parentLock->acquire());
				$this->fail('Should throw an exception of ' . LockedException::class . ". ($savePid)");
			} catch (Throwable $e) {
				// All good
				$this->assertInstanceOf(LockedException::class, $e);
			}
			
			try {
				$savePid = $parentLock->getLockState();
				$parentLock->acquire($acquireWait);
				$this->fail('Should throw an exception of ' . TimeoutExpired::class . ". ($savePid)");
			} catch (Throwable $e) {
				$this->assertInstanceOf(TimeoutExpired::class, $e);
			}
			
			$this->assertTrue($parentLock->isLocked());
			$this->assertFalse($parentLock->isMine());
			
			$this->assertFalse($childLock->isLocked());
			$this->assertFalse($childLock->isMine());
			sleep(1);
			$this->assertEquals($childLock, $childLock->acquire($acquireWait));
			$this->assertFalse($childLock->isLocked());
			$this->assertTrue($childLock->isMine());
			$childLock->entityManager()->getConnection()->close();
			
			/* Die without releasing here - is there some hook which releases upon fork/exit? Hmmm. Should know this. */
			exit(0);
		} else {
			/* I am the parent, close database connection and reconnect (give connection to child). */
			$lock->entityManager()->getConnection()->close();
			$childLock = Lock::instance($this->testApplication, $childName);
			$parentCrackLock = Lock::instance($this->testApplication, $crackChildName);
			$start = microtime(true);
			$cracked = false;
			while (!$childLock->isLocked()) {
				if (!$cracked && $parentCrackLock->isLocked()) {
					$this->assertEquals($parentCrackLock, $parentCrackLock->crack());
					$cracked = true;
				}
				$this->assertFalse($parentCrackLock->isLocked());
				sleep(1);
				$delta = microtime(true) - $start;
				$this->assertLessThan($acquireWait * 10, $delta, "Child process $secondPid did not lock the lock in time ($delta seconds)");
			}
			pcntl_waitpid($secondPid, $status);
			$this->assertEquals(0, $status, "Exit code from forked process is not 0: $status");
			
			// Release parentName
			$this->assertEquals($lock, $lock->release());
			
			$this->assertFalse($lock->isLocked());
			/* Process is dead therefore child is NOT locked */
			$this->assertFalse($childLock->isLocked());
			$this->assertTrue($cracked, 'Never cracked child lock?');
		}
	}
}
