<?php
/**
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
namespace zesk;

class CSV_Writer_Test extends Test_Unit {
	protected $load_modules = array(
		"csv",
	);

	public function test_main() {
		$x = new CSV_Writer();

		$f = $this->test_sandbox("csv_writer.csv");
		$x->file($f);

		$success = false;

		try {
			$name = "omap";
			$map = array(
				"Title" => "B",
			);
			$x->add_object_map($name, $map);
		} catch (Exception_Semantics $e) {
			$success = true;
		}
		$this->assert($success);

		$x->object_names();

		$success = false;

		try {
			$name = "test";
			$map = array(
				"Title" => "B",
			);
			$x->add_translation_map($name, $map);
		} catch (Exception_Semantics $e) {
			$success = true;
		}
		$this->assert($success);

		$success = false;

		try {
			$name = "SomeObject";
			$fields = array(
				"Title" => "Name",
			);
			$x->set_object($name, $fields);
		} catch (Exception_Key $e) {
			$success = true;
		}
		$this->assert($success);

		$set_headers = array(
			"Title",
			"CodeName",
			"Something",
		);
		$this->assert_equal($x->set_headers($set_headers, false), $x);

		$headers = $x->headers();
		$this->assert_equal($headers, $set_headers);

		$name = "omap";
		$map = array(
			"B" => "Title",
		);
		$defaultMap = null;
		$x->add_object_map($name, $map, $defaultMap);

		$name = "CodeName";
		$map = array(
			"Title" => "C",
		);
		$x->add_translation_map($name, $map);

		$name = "omap";
		$map = array(
			"B" => "Title",
		);
		$x->set_object($name, $fields);

		$row = array();
		$x->set_row($row);

		$col = null;
		$data = null;
		$x->set_column($col, $data);

		$x->write_row();

		$x->close();

		$x->headers();

		$x->filename();

		$x->row_index();
	}

	public function badkeys() {
		return array(
			"f!rst",
			"2ECOND",
			"THURD",
			"4",
			"random",
			"5 ",
			"six",
		);
	}

	public function goodkeys() {
		return array(
			"first",
			"SECOND",
			"tHIRD",
			"4th",
			5,
		);
	}

	/**
	 * @data_provider badkeys
	 * @expectedException zesk\Exception_Key
	 */
	public function test_badkey($badkey) {
		$x = new CSV_Writer();
		$x->set_headers(array(
			"First",
			"Second",
			"Third",
			"4th",
			"5",
			6,
		), false);

		$x->add_object_map("test", array(
			"ID" => $badkey,
		));
	}

	/**
	 * @data_provider goodkeys
	 */
	public function test_goodkey($key) {
		$x = new CSV_Writer();
		$x->set_headers(array(
			"First",
			"Second",
			"Third",
			"4th",
			"5",
			6,
		), false);

		$x->add_object_map("test", array(
			"ID" => $key,
		));
	}
}
