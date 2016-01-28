<?php
/**
 * $URL:
 * https://code.marketacumen.com/zesk/trunk/modules/csv/classes/csv/reader/test/CSV_Reader_Iterator.phpt
 * $
 *
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
class csv_reader_iterator_test extends Test_Unit {
	function initialize() {
		Module::load('csv');
	}
	function test_main() {
		$test_file = $this->test_sandbox("test.csv");
		file_put_contents($test_file, "A,B,C,Dee\n0,0,0,1\n0,0,2,0\n0,3,0,0\n4,0,0,0\nend,end,end,end");
		$csv = new CSV_Reader();
		$csv->setFile($test_file);
		$options = false;
		$testx = new CSV_Reader_Iterator($csv, $options);

		$result = array();
		foreach ($testx as $index => $row) {
			$result[$index] = $row;
		}
		//log::notice(php::dump($result));
		$this->assert_equal_array($result, array(
			1 => array(
				"A" => "0",
				"B" => "0",
				"C" => "0",
				"Dee" => "1"
			),
			array(
				"A" => "0",
				"B" => "0",
				"C" => "2",
				"Dee" => "0"
			),
			array(
				"A" => "0",
				"B" => "3",
				"C" => "0",
				"Dee" => "0"
			),
			array(
				"A" => "4",
				"B" => "0",
				"C" => "0",
				"Dee" => "0"
			),
			array(
				"A" => "end",
				"B" => "end",
				"C" => "end",
				"Dee" => "end"
			)
		));
	}
}