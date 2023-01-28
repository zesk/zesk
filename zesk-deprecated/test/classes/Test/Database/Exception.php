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
class Test_Database_Exception extends UnitTest {
	protected array $load_modules = ['mysql', 'database'];

	public function database() {
		return $this->application->databaseRegistry($this->option('database'));
	}

	/**
	 */
	public function test_main(): void {
		$database = $this->application->databaseRegistry();
		$this->assertInstanceOf(Database::class, $database);
		for ($i = 0; $i < 100; $i++) {
			$code = 4123 + $i;
			$x = new Database_Exception($database, 'hello {dude}', [
				'dude' => 'world!',
			], $code, new Exception('previous'));

			$this->_test_exception($x, 'hello world!', $code);
		}
	}
}
