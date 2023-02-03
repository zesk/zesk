<?php declare(strict_types=1);
namespace zesk;

class Exception_TestCase extends UnitTest {
	public function simple_exceptions() {
		return [
			new \Exception('message', 123, new \Exception('previous', 345)),
			new Exception('Hello {thing}', [
				'thing' => 'world',
			]),
		];
	}

	/**
	 * @dataProvider simple_exceptions
	 * @param \Exception $e
	 */
	public function exception_test(\Exception $e): void {
		$this->assertIsString($e->getMessage());

		$this->assertIsInteger($e->getCode());

		$this->assertIsString($e->getFile());

		$e->getLine();

		$e->getTrace();

		$e->getPrevious();

		$e->getTraceAsString();

		$e->__toString();

		$variables = Exception::exceptionVariables($e);
		$this->assertEquals($e::class, $variables['class']);
		$this->assertEquals($e::class, $variables['exceptionClass']);
		$this->assertEquals($e->getCode(), $variables['code']);
		$this->assertEquals($e->getMessage(), $variables['message']);
		$this->assertEquals($e->getFile(), $variables['file']);
		$this->assertEquals($e->getLine(), $variables['line']);
		$this->assertIsArray($variables['trace']);
		$this->assertIsString($variables['backtrace'], 'backtrace is string');
		$this->assertIsString($variables['rawMessage'], 'rawMessage is string');
	}
}
