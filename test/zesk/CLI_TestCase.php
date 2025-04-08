<?php
declare(strict_types=1);

namespace test\zesk;

use zesk\Exception\CommandFailed;
use zesk\Types;

class CLI_TestCase extends TestCase
{
	public static function data_CLI(): array
	{
		return [
			[
				['Unable to change directory to "/root"'],
				[
					'--cd',
					'/root/',
					'cwd',
				],
				true,
			],
			[
				['--cd directory is required'],
				['--cd'],
				true,
			],
			[
				[
					'/etc',
					'/zesk',
				],
				'--cd /etc/ /zesk/zesk.application.php cwd -- --cd /zesk/ cwd',
				false,
			],
			[
				[
					'["1","2","3","4"]',
					'["2","3","4","5","6"]',
				],
				'arguments 1 2 3 4 -- arguments 2 3 4 5 6 --',
				false,
			],
		];
	}

	/**
	 * @param array $expectedStrings
	 * @param string|array $args
	 * @param bool $captureError
	 * @return void
	 * @dataProvider data_CLI
	 * @throws CommandFailed
	 */
	public function test_CLI(array $expectedStrings, string|array $args, bool $captureError): void
	{
		foreach ($expectedStrings as $expected) {
			$result = $this->zeskBinExecute(Types::toList($args, [], ' '), $captureError);
			$result = implode("\n", $result);
			$this->assertStringContainsString($expected, $result);
		}
	}
}
