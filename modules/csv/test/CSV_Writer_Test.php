<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
namespace zesk;

class CSV_Writer_Test extends Test_Unit {
	protected array $load_modules = [
		"csv",
	];

	public function test_main(): void {
		$x = new CSV_Writer();

		$f = $this->test_sandbox("csv_writer.csv");
		$x->file($f);

		$success = false;

		try {
			$name = "omap";
			$map = [
				"Title" => "B",
			];
			$x->add_object_map($name, $map);
		} catch (Exception_Semantics $e) {
			$success = true;
		}
		$this->assert($success);

		$x->object_names();

		$success = false;

		try {
			$name = "test";
			$map = [
				"Title" => "B",
			];
			$x->add_translation_map($name, $map);
		} catch (Exception_Semantics $e) {
			$success = true;
		}
		$this->assert($success);

		$success = false;

		try {
			$name = "SomeObject";
			$fields = [
				"Title" => "Name",
			];
			$x->set_object($name, $fields);
		} catch (Exception_Key $e) {
			$success = true;
		}
		$this->assert($success);

		$set_headers = [
			"Title",
			"CodeName",
			"Something",
		];
		$this->assert_equal($x->set_headers($set_headers, false), $x);

		$headers = $x->headers();
		$this->assert_equal($headers, $set_headers);

		$name = "omap";
		$map = [
			"B" => "Title",
		];
		$defaultMap = null;
		$x->add_object_map($name, $map, $defaultMap);

		$name = "CodeName";
		$map = [
			"Title" => "C",
		];
		$x->add_translation_map($name, $map);

		$name = "omap";
		$map = [
			"B" => "Title",
		];
		$x->set_object($name, $fields);

		$row = [];
		$x->set_row($row);

		$col = "foo";
		$data = null;
		$x->set_column($col, $data);

		$x->write_row();

		$x->close();

		$x->headers();

		$x->filename();

		$x->row_index();
	}

	public function badkeys() {
		return [
			"f!rst",
			"2ECOND",
			"THURD",
			"4",
			"random",
			"5 ",
			"six",
		];
	}

	public function goodkeys() {
		return [
			"first",
			"SECOND",
			"tHIRD",
			"4th",
			5,
		];
	}

	/**
	 * @data_provider badkeys
	 * @expectedException zesk\Exception_Key
	 */
	public function test_badkey($badkey): void {
		$x = new CSV_Writer();
		$x->set_headers([
			"First",
			"Second",
			"Third",
			"4th",
			"5",
			6,
		], false);

		$x->add_object_map("test", [
			"ID" => $badkey,
		]);
	}

	/**
	 * @data_provider goodkeys
	 */
	public function test_goodkey($key): void {
		$x = new CSV_Writer();
		$x->set_headers([
			"First",
			"Second",
			"Third",
			"4th",
			"5",
			6,
		], false);

		$x->add_object_map("test", [
			"ID" => $key,
		]);
	}
}
