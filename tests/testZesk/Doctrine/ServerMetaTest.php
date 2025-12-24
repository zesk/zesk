<?php

declare(strict_types=1);

namespace testZesk\Doctrine;

use Doctrine\ORM\Exception\ORMException;
use zesk\Doctrine\Server;
use zesk\Doctrine\ServerMeta;
use zesk\Exception\KeyNotFound;

/**
 * @coversDefaultClass \zesk\Doctrine\Server
 * @covers \zesk\Doctrine\ServerMeta
 */
class ServerMetaTest extends ModelTestCase
{
	public array $entities = [
		Server::class,
		ServerMeta::class,
	];

	public string $testName0 = 'thing';

	public string $testValue0 = 'value';

	public string $testArrayName0 = 'name';

	public array $testArrayValue0 = [
		'john',
		'paul',
	];

	/**
	 * @throws KeyNotFound
	 * @throws ORMException
	 * @covers ::clearMeta ::meta ::setMeta
	 */
	public function test_ServerMeta(): void
	{
		$server = Server::singleton($this->testApplication);

		$server->clearMeta();

		$found = 0;
		foreach ($server->metas as $meta) {
			/* @var ServerMeta $meta */
			++$found;
		}
		$this->assertEquals(0, $found);

		$this->doMetas($server);

		$this->assertEquals($this->testValue0, $server->meta($this->testName0));
		$this->assertEquals($this->testArrayValue0, $server->meta($this->testArrayName0));

		$server->entityManager()->refresh($server);

		$found = 0;
		foreach ($server->metas as $meta) {
			/* @var ServerMeta $meta */
			++$found;
		}
		$this->assertEquals(2, $found);

		$server->clearMeta();
		$found = 0;
		foreach ($server->metas as $meta) {
			/* @var ServerMeta $meta */
			++$found;
		}
		$this->assertEquals(0, $found);
	}

	/**
	 * @throws ORMException
	 */
	public function doMetas(Server $server): void
	{
		$server->setMeta($this->testName0, $this->testValue0);
		$server->setMeta($this->testArrayName0, $this->testArrayValue0);

		$server->entityManager()->flush();
	}
}
