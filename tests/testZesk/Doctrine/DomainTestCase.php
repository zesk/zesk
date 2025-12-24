<?php
declare(strict_types=1);

namespace testZesk\Doctrine;

use zesk\Doctrine\Domain;
use zesk\Exception\FileNotFound;
use zesk\Exception\FilePermission;

class DomainTestCase extends ModelTestCase
{
	/**
	 * @throws FilePermission
	 * @throws FileNotFound
	 */
	public function test_Domain(): void
	{
		$domain = Domain::domainFactory($this->application, 'example.com');

		$this->assertModel($domain);
	}
}
