<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace test;

use zesk\CSV\MapReader;
use zesk\Exception\KeyNotFound;
use zesk\PHPUnit\TestCase;

class ReaderMapTest extends TestCase
{
	protected array $load_modules = [
		'CSV',
	];

	public function sample_reader(): MapReader
	{
		$x = new MapReader();

		$f = $this->test_sandbox('test.csv');
		file_put_contents($f, str_repeat("A,B,C,D,E,F,G\n0,1,2,3,4,5,6\na,b,c,d,e,f,g\n", 10));
		$x->setFilename($f);

		$x->read_headers();
		return $x;
	}

	public function test_main(): void
	{
		$x = $this->sample_reader();
		$success = [];

		try {
			$x->readMap('Hello');
			$success[] = false;
		} catch (KeyNotFound) {
			$success[] = true;
		}

		try {
			$x->readMap('');
			$success[] = false;
		} catch (KeyNotFound) {
			$success[] = true;
		}

		try {
			$x->readMap('nokey');
			$success[] = false;
		} catch (KeyNotFound $e) {
			$success[] = true;
		}
		$this->assertCount(count($success), array_filter($success));

		$x->read_row();

		$lower = false;
		$x->read_row_assoc();

		$offset = 1;
		$x->skip($offset);

		$x->close();

		$headers = [
			[
				'A',
				'B',
				'C',
				'D',
			],
		];
		$is_map = false;
		$x->setHeaders($headers, false);

		$x->headers();

		$this->assertIsString($x->filename());

		$x->rowIndex();
	}

	/**
	 */
	public function test_bad_read_map_key(): void
	{
		$this->expectException(KeyNotFound::class);
		$x = $this->sample_reader();
		$success = false;
		$map = [
			'Dude' => 'X',
		];
		$mapTypes = null;
		$defaultMap = null;
		$x->readMap('Hello', $map, $mapTypes, $defaultMap);
	}
}
