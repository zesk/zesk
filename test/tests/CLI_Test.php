<?php
declare(strict_types=1);

namespace zesk;

class CLI_Test extends UnitTest {
	public function data_CLI(): array {
		return [
			[['No zesk *.application.php found in: /root'], ['--cd', '/root/', 'cwd'], true],
			[['/root', '/root'], '--cd /etc/ --search /zesk/ --cd /root/ cwd cwd', false],
			[['/root', '/zesk'], '--cd /etc/ --search /zesk/ --cd /root/ cwd -- --cd /zesk/ cwd', false],
			[['["1","2","3","4"]', '["2","3","4","5","6"]'], 'arguments 1 2 3 4 -- arguments 2 3 4 5 6 --', false],
		];
	}

	/**
	 * @param array $expected
	 * @param string|array $args
	 * @param bool $captureError
	 * @return void
	 * @throws Exception_Command
	 * @dataProvider data_CLI
	 */
	public function test_CLI(array $expected, string|array $args, bool $captureError): void {
		$this->assertEquals($expected, $this->zeskBinExecute(toList($args, [], ' '), $captureError));
	}
}
