<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk;

use Exception as BaseException;
use Throwable;
use zesk\Exception\NotFoundException;
use zesk\Exception\SyntaxException;
use zesk\Exception\UnsupportedException;
use zesk\PHPUnit\ExceptionTestCase;

class ThrowableTest extends ExceptionTestCase
{
	public static function data_simpleThrowables(): array
	{
		return [
			[new BaseException('message', 123, new BaseException('previous', 345))], [
				new SyntaxException('Hello {thing}', [
					'thing' => 'world',
				]),
			], [
				new NotFoundException('Hello {thing}', [
					'thing' => 'world',
				]),
			], [
				new UnsupportedException('Hello {thing}', [
					'thing' => 'world',
				]),
			],
		];
	}

	/**
	 * @param Throwable $t
	 * @return void
	 * @dataProvider data_simpleThrowables
	 */
	public function test_simpleThrowables(Throwable $t): void
	{
		$this->assertThrowable($t);
	}
}
