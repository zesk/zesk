<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
namespace zesk;

class Database_Exception_Test extends Exception_TestCase {
	public array $load_modules = [
		'Database',
		'MySQL',
	];

	public function test_basics(): void {
		$testx = new Database_Exception($this->test_database(), 'Basic test exception');

		$this->exception_test($testx);
	}
}
