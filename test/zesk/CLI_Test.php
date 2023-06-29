<?php
declare(strict_types=1);

namespace zesk;

class CLI_Test extends UnitTest
{
	public static function data_CLI(): array
	{
		return [
			[['Unable to change directory to "/root/"'], ['--cd',
				'/root/', 'cwd', ], true],
			[['--cd missing directory argument'], ['--cd'], true],
			[['/etc', '/zesk'], '--cd /etc/ /zesk/zesk.application.php cwd -- --cd /zesk/ cwd', false],
			[['["1","2","3","4"]', '["2","3","4","5","6"]'], 'arguments 1 2 3 4 -- arguments 2 3 4 5 6 --', false],
		];
	}

	/**
	 * @param array $expected
	 * @param string|array $args
	 * @param bool $captureError
	 * @return void
	 * @dataProvider data_CLI
	 */
	public function test_CLI(array $expected, string|array $args, bool $captureError): void
	{
		$this->assertEquals($expected, $this->zeskBinExecute(Types::toList($args, [], ' '), $captureError));
	}
}
