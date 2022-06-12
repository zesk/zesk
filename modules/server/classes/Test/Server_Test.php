<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Server_Test extends Test_Unit {
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
