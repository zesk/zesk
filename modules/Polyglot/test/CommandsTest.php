<?php
declare(strict_types=1);

namespace zesk\Polyglot;

use zesk\Exception\ConfigurationException;
use zesk\Exception\NotFoundException;
use zesk\Exception\ParameterException;
use zesk\Exception\UnsupportedException;
use zesk\Polyglot\Command\Export;
use zesk\Polyglot\Command\Translate;
use zesk\Polyglot\Command\Update;
use zesk\TestApplicationUnitTest;

class CommandsTest extends TestApplicationUnitTest
{
	public function dataCommands(): array
	{
		return [
			[
				Export::class, ['--source', '/tmp/not-found/'], 1, '/tmp/not-found/ was not found',
			],
			[
				Translate::class, ['--help'], 1, '',
			],
			[
				Update::class, [], 0, '',
			],
		];
	}

	/**
	 * @param string $class
	 * @param array $testArguments
	 * @param int $expectedStatus
	 * @param string $expectedOutputOrPattern
	 * @return void
	 * @throws ConfigurationException
	 * @throws NotFoundException
	 * @throws ParameterException
	 * @throws UnsupportedException
	 * @dataProvider dataCommands
	 */
	public function test_commands(string $class, array $testArguments, int $expectedStatus, string
										 $expectedOutputOrPattern): void
	{
		$this->assertCommandClass($class, $testArguments, $expectedStatus, $expectedOutputOrPattern);
	}
}
