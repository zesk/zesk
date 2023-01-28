<?php declare(strict_types=1);
/**
 *
 */
namespace Server\classes\Test;

use zesk\Server;
use zesk\UnitTest;

/**
 *
 * @author kent
 *
 */
class Server_Test extends UnitTest {
	protected array $load_modules = [
		'ORM',
		'MySQL',
	];

	protected function initialize(): void {
		$this->schema_synchronize(Server::class);
	}

	public function test_Server(): void {
		$this->application->configuration->HOST = 'localhost';

		$testx = new Server($this->application);
		$this->assert_instanceof($testx, Server::class);

		$testx = Server::singleton($this->application);

		$this->assert_instanceof($testx, Server::class);
		$path = '/';
		$testx->id = 1;
		$testx->updateState($path);
	}
}
