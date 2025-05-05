<?php

declare(strict_types=1);

namespace test\zesk\Doctrine;

use zesk\Doctrine\ModelTestCase;
use zesk\Doctrine\Server;
use zesk\StringTools;
use zesk\Timestamp;

class ServerTest extends ModelTestCase {
	
	protected array $entities = [Server::class];
	
	public function test_Server(): void
	{
		xdebug_break();
		$server = Server::singleton($this->application);
		
		$now = Timestamp::now();
		
		$this->assertInstanceOf(Server::class, $server);
		
		$this->assertFalse($server->alive->before($now), 'Server ' . $server->id . ' not alive before ' . $now);
		$earlier = $now->addUnit(-60);
		$this->assertTrue($server->alive->after($earlier), 'Server ' . $server->id . ' not alive after ' . $earlier);
	}
}
