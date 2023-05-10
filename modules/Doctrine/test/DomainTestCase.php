<?php

namespace test;

use zesk\Doctrine\Domain;
use zesk\Doctrine\ModelUnitTest;

class DomainTest extends ModelUnitTest {
	public function test_Domain(): void {
		$domain = Domain::domainFactory($this->application, "example.com");
		$this->assertInstanceOf(Domain::class, $domain);

		$this->assertORMObject($domain);

	}
}
