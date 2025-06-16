<?php
declare(strict_types=1);
/**
 *
 */

namespace testZesk;

use Exception as BaseException;
use Throwable;
use zesk\Exception\NotFoundException;
use zesk\Exception\SyntaxException;
use zesk\Exception\UnsupportedException;
use zesk\PHPUnit\ExceptionTestCase;

class ThrowableTest extends ExceptionTestCase
{
	/**
	 * @covers       \zesk\Exception\SyntaxException
	 */
	public function testSyntaxException(): void
	{
		$this->assertThrowable(new SyntaxException('Hello {thing}', [
			'thing' => 'world',
		]));
	}

	/**
	 * @covers       \zesk\Exception\NotFoundException
	 */
	public function testNotFoundException(): void
	{
		$this->assertThrowable(new NotFoundException('Hello {thing}', [
			'thing' => 'world',
		]));
	}

	/**
	 * @covers       \zesk\Exception\UnsupportedException
	 */
	public function testUnsupportedException(): void
	{
		$this->assertThrowable(new UnsupportedException('Hello {thing}', [
			'thing' => 'world',
		]));
	}
}
