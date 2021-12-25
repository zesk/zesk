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
class Test_Database_Exception extends Test_Unit {
	public function database() {
		return $this->application->database_registry($this->option("database"));
	}

	/**
	 */
	public function test_main(): void {
		$database = $this->application->database_registry();
		$this->assertInstanceOf(zesk\Database::class, $database);
		for ($i = 0; $i < 100; $i++) {
			$code = 4123 + $i;
			$x = new Database_Exception($database, "hello {dude}", [
				"dude" => "world!",
			], $code, new Exception("previous"));

			$this->_test_exception($x, "hello world!", $code);
		}
	}

	/**
	 *
	 * @param Database_Exception $x
	 * @param unknown $expected_message
	 * @param unknown $expected_code
	 */
	protected function _test_exception(Database_Exception $x, $expected_message = null, $expected_code = null): void {
		$message = $x->getMessage();
		if ($expected_message !== null) {
			$this->assert_equal($message, $expected_message);
		}
		$code = $x->getCode();
		if ($expected_code !== null) {
			$this->assert_equal($code, $expected_code);
		}

		$this->assert_is_string($x->__toString());

		// I assume this is here to just make sure they do not explode/coverage, as these are all internal
		$this->assert_true(is_file($x->getFile()));
		$this->assert_is_integer($x->getLine());
		$this->assert_is_array($x->getTrace());
		$this->assert_is_string($x->getTraceAsString());
	}
}
