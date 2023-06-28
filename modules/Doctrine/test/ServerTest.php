<?php

declare(strict_types=1);

namespace test;

use zesk\Doctrine\ModelTestCase;
use zesk\Doctrine\Server;
use zesk\Timestamp;

class ServerTest extends ModelTestCase
{
	public function test_Server(): void
	{
		$server = Server::singleton($this->application);

		$this->assertInstanceOf(Server::class, $server);

		$now = Timestamp::now();

		$this->assertTrue($server->alive->before($now));
		$this->assertTrue($server->alive->after($now->addUnit(-60)));
	}
}
