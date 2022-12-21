<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk\CSV;

use zesk\UnitTest;
use zesk\Exception_Key;

class CSV_Reader_Test extends UnitTest {
	protected array $load_modules = [
		'CSV',
	];

	public function sample_reader(): MapReader {
		$x = new MapReader();

		$f = $this->test_sandbox('test.csv');
		file_put_contents($f, "A,B,C,D,E,F,G\n0,1,2,3,4,5,6\na,b,c,d,e,f,g\n");
		$x->setFilename($f);

		$x->read_headers();
		return $x;
	}

	public function test_main(): void {
		$x = $this->sample_reader();
		$map = [
			'A' => 'Dude',
		];
		$x->readMap('Hello', $map);

		$x->readMap('');

		$success = false;

		try {
			$name = null;
			$x->readMap('nokey');
		} catch (Exception_Key $e) {
			$success = true;
		}
		$this->assertTrue($success);

		$x->read_row();

		$lower = false;
		$x->read_row_assoc($lower);

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
		$x->set_headers($headers, false);

		$x->headers();

		$this->assertIsString($x->filename());

		$x->rowIndex();
	}

	/**
	 */
	public function test_bad_read_map_key(): void {
		$this->expectException(Exception_Key::class);
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
