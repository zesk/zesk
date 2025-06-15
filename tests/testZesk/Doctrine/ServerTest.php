<?php

declare(strict_types=1);

namespace testZesk\Doctrine;

use Doctrine\ORM\Exception\ORMException;
use zesk\Doctrine\Server;
use zesk\Timestamp;

/**
 * @coversDefaultClass \zesk\Doctrine\Server
 */
class ServerTest extends ModelTestCase
{
	protected array $entities = [Server::class];

	/**
	 * @return void
	 * @throws ORMException
	 * @covers ::singleton
	 */
	public function test_Server(): void
	{
		$server = Server::singleton($this->testApplication);

		$now = Timestamp::now();


		$this->assertTrue($server->alive->before($now), 'Server ' . $server->id . ' not alive before ' . $now . ' (Alive is ' . $server->alive . ')');
		$earlier = $now->addUnit(-60);
		$this->assertTrue($server->alive->after($earlier), 'Server ' . $server->id . ' not alive after ' . $earlier . ' (Alive is ' . $server->alive . ')');
	}
}
