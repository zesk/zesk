<?php declare(strict_types=1);

namespace test;

use zesk\Doctrine\Domain;
use zesk\Doctrine\ModelTestCase;

class DomainTestCase extends ModelTestCase {
	public function test_Domain(): void {
		$domain = Domain::domainFactory($this->application, 'example.com');
		$this->assertInstanceOf(Domain::class, $domain);

		$this->assertModel($domain);
	}
}
