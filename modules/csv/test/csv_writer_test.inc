<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/csv/test/csv_writer_test.inc $
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
class CSV_Writer_Test extends Test_Unit {
	protected $load_modules = array(
		"csv"
	);
	function test_main() {
		$options = false;
		$x = new CSV_Writer($options);
		
		$f = $this->test_sandbox("csv_writer.csv");
		$x->file($f);
		
		$success = false;
		try {
			$name = "omap";
			$map = array(
				"Title" => "B"
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
				"Title" => "B"
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
				"Title" => "Name"
			);
			$x->set_object($name, $fields);
		} catch (Exception_Key $e) {
			$success = true;
		}
		$this->assert($success);
		
		$x->setHeaders(array(
			"Title",
			"CodeName",
			"Something"
		));
		
		$name = "omap";
		$map = array(
			"B" => "Title"
		);
		$defaultMap = null;
		$x->add_object_map($name, $map, $defaultMap);
		
		$name = "CodeName";
		$map = array(
			"Title" => "C"
		);
		$x->add_translation_map($name, $map);
		
		$name = "omap";
		$map = array(
			"B" => "Title"
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
		
		$name = null;
		$x->columnIsEmpty($name);
		
		$name = null;
		$default = false;
		$x->columnGet($name, $default);
		
		$x->rowIndex();
	}
}
