<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/csv/classes/csv/test/cvs_reader_map_test.inc $
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
class CSV_Reader_Test extends Test_Unit {
	function initialize() {
		Module::load('csv');
	}
	function test_main() {
		$options = false;
		$x = new CSV_Reader_Map($options);

		$f = $this->test_sandbox("test.csv");
		file_put_contents($f, "A,B,C,D,E,F,G\n0,1,2,3,4,5,6\na,b,c,d,e,f,g\n");
		$x->filename($f);

		$x->read_headers();
		$success = false;
		try {
			$map = array(
				"Dude" => "A"
			);
			$mapTypes = null;
			$defaultMap = null;
			$x->read_map("Hello", $map, $mapTypes, $defaultMap);
		} catch (Exception_Semantics $e) {
			$success = true;
		}
		$this->assert($success);

		$x->read_headers();

		$map = array(
			"A" => "Dude"
		);
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

		$headers = array(
			array(
				"A",
				"B",
				"C",
				"D"
			)
		);
		$is_map = false;
		$x->setHeaders($headers, $is_map);

		$x->headers();

		$x->fileName();

		$name = null;
		$x->columnIsEmpty($name);

		$name = null;
		$default = false;
		$x->columnGet($name, $default);

		$x->rowIndex();

		echo basename(__FILE__) . ": success\n";
	}
}
