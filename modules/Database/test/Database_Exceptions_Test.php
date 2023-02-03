<?php

declare(strict_types=1);

/**
 *
 */

namespace zesk;

/**
 *
 * @author kent
 *
 */
class Database_Exceptions_Test extends UnitTest {
	protected array $load_modules = ['Database', 'MySQL'];

	/**
	 * @return Database
	 */
	public function database(): Database {
		return $this->application->databaseRegistry($this->option('database', ''));
	}

	/**
	 * @param Database_Exception $x
	 * @param string|null $expected_message
	 * @param int|null $expected_code
	 * @return void
	 */
	protected function _test_exception(Database_Exception $x, string $expected_message = null, int $expected_code = null): void {
		$message = $x->getMessage();
		if ($expected_message !== null) {
			$this->assertEquals($message, $expected_message);
		}
		$code = $x->getCode();
		if ($expected_code !== null) {
			$this->assertEquals($code, $expected_code);
		}

		$this->assertIsString($x->__toString());

		// I assume this is here to just make sure they do not explode/coverage, as these are all internal
		$this->assertTrue(is_file($x->getFile()));
		$this->assertIsInteger($x->getLine());
		$this->assertIsArray($x->getTrace());
		$this->assertIsString($x->getTraceAsString());
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

	/**
	 *
	 * @param Database_Exception $x
	 */
	protected function validate_exception(Database_Exception $x): void {
		$message = $x->getMessage();
		$this->assertIsString($message);
		$code = $x->getCode();
		$this->assertIsInteger($code);
		$this->assertIsString($x->__toString());

		// I assume this is here to just make sure they do not explode/coverage, as these are all internal
		$this->assertTrue(is_file($x->getFile()));
		$this->assertIsInteger($x->getLine());
		$this->assertIsArray($x->getTrace());
		$this->assertIsString($x->getTraceAsString());

		$this->assertIsString(strval($x));
	}

	public function test_duplicate(): void {
		$e = new Database_Exception_Duplicate($this->database(), 'INSERT INTO foo ( id, name ) VALUES ( 4, \'dude\' )', 'duplicate for primary key id');
		$this->validate_exception($e);
	}

	public function test_schema(): void {
		$e = new Database_Exception_Schema($this->database());
		$this->validate_exception($e);
	}

	public function test_base(): void {
		$e = new Database_Exception($this->database(), 'Basic test exception');
		$this->validate_exception($e);
	}
}
