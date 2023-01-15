<?php
declare(strict_types=1);

namespace zesk;

class CLI_Test extends UnitTest {
	public function data_CLI(): array {
		return [
			[['/zesk/bin/zesk.sh: 20: cd: can\'t cd to /root/', '--cd Unable to change directory to "/root/"'], ['--cd',
				'/root/', 'cwd'], true],
			[['/etc', '--cd Unable to change directory to "/root/"'], '--cd /etc/ --search /zesk/ cwd -- --cd /root/ cwd',
				true],
			[['/etc', '/zesk'], '--cd /etc/ --search /zesk/ cwd -- --cd /zesk/ cwd', false],
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
