<?php

declare(strict_types=1);

/**
 *
 */

namespace zesk\Database;

use zesk\Database\Exception\Duplicate;
use zesk\Database\Exception\SchemaException;
use zesk\PHPUnit\TestCase;

/**
 *
 * @author kent
 *
 */
class ExceptionsTest extends TestCase
{
	protected array $load_modules = ['Database', 'MySQL'];

	/**
	 * @return Base
	 */
	public function database(): Base
	{
		return $this->application->databaseRegistry($this->option('database', ''));
	}

	/**
	 * @param Exception $x
	 * @param string|null $expected_message
	 * @param int|null $expected_code
	 * @return void
	 */
	protected function _test_exception(Exception $x, string $expected_message = null, int $expected_code = null): void
	{
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
	public function test_main(): void
	{
		$database = $this->application->databaseRegistry();
		$this->assertInstanceOf(Base::class, $database);
		for ($i = 0; $i < 100; $i++) {
			$code = 4123 + $i;
			$x = new Exception($database, 'hello {dude}', [
				'dude' => 'world!',
			], $code, new Exception($database, 'previous'));

			$this->_test_exception($x, 'hello world!', $code);
		}
	}

	/**
	 *
	 * @param Exception $x
	 */
	protected function validate_exception(Exception $x): void
	{
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

	public function test_duplicate(): void
	{
		$e = new Duplicate($this->database(), 'INSERT INTO foo ( id, name ) VALUES ( 4, \'dude\' )', 'duplicate for primary key id');
		$this->validate_exception($e);
	}

	public function test_schema(): void
	{
		$e = new SchemaException($this->database());
		$this->validate_exception($e);
	}

	public function test_base(): void
	{
		$e = new Exception($this->database(), 'Basic test exception');
		$this->validate_exception($e);
	}
}
