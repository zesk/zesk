<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
namespace zesk;

class CSV_Reader_Test extends Test_Unit {
	protected array $load_modules = [
		"CSV",
	];

	public function sample_reader() {
		$options = false;
		$x = new CSV_Reader_Map($options);

		$f = $this->test_sandbox("test.csv");
		file_put_contents($f, "A,B,C,D,E,F,G\n0,1,2,3,4,5,6\na,b,c,d,e,f,g\n");
		$x->filename($f);

		$x->read_headers();
		return $x;
	}

	public function test_main(): void {
		$x = $this->sample_reader();
		$map = [
			"A" => "Dude",
		];
		$x->read_map("Hello", $map);

		$x->read_map();

		$success = false;

		try {
			$name = null;
			$x->read_map("nokey");
		} catch (Exception_Key $e) {
			$success = true;
		}
		$this->assert($success);

		$x->read_row();

		$lower = false;
		$x->read_row_assoc($lower);

		$offset = 1;
		$x->skip($offset);

		$x->close();

		$headers = [
			[
				"A",
				"B",
				"C",
				"D",
			],
		];
		$is_map = false;
		$x->set_headers($headers, false);

		$x->headers();

		$x->filename();

		$x->row_index();
	}

	/**
	 * @expectedException zesk\Exception_Key
	 */
	public function test_bad_read_map_key(): void {
		$x = $this->sample_reader();
		$success = false;
		$map = [
			"Dude" => "X",
		];
		$mapTypes = null;
		$defaultMap = null;
		$x->read_map("Hello", $map, $mapTypes, $defaultMap);
	}
}
