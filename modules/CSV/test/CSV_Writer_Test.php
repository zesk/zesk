<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\CSV;

use zesk\UnitTest;
use zesk\Exception_Semantics;
use zesk\Exception_Key;

class CSV_Writer_Test extends UnitTest {
	protected array $load_modules = [
		'csv',
	];

	public function test_main(): void {
		$x = new Writer();

		$f = $this->test_sandbox('csv_writer.csv');
		$x->setFile($f);

		$success = false;

		try {
			$name = 'omap';
			$map = [
				'Title' => 'B',
			];
			$x->add_object_map($name, $map);
		} catch (Exception_Semantics $e) {
			$success = true;
		}
		$this->assertTrue($success);

		$this->assertIsArray($x->object_names());

		$success = false;

		try {
			$name = 'test';
			$map = [
				'Title' => 'B',
			];
			$x->add_translation_map($name, $map);
		} catch (Exception_Semantics $e) {
			$success = true;
		}
		$this->assertTrue($success);

		$success = false;

		try {
			$name = 'SomeObject';
			$fields = [
				'Title' => 'Name',
			];
			$x->setObject($name, $fields);
		} catch (Exception_Key $e) {
			$success = true;
		}
		$this->assertTrue($success);

		$set_headers = [
			'Title', 'CodeName', 'Something',
		];
		$this->assertInstanceOf(Base::class, $x->set_headers($set_headers, false));

		$headers = $x->headers();
		$this->assertEquals($headers, $set_headers);

		$name = 'omap';
		$map = [
			'B' => 'Title',
		];
		$defaultMap = null;
		$x->add_object_map($name, $map, $defaultMap);

		$name = 'CodeName';
		$map = [
			'Title' => 'C',
		];
		$x->add_translation_map($name, $map);

		$name = 'omap';
		$map = [
			'B' => 'Title',
		];
		$x->setObject($name, $map);

		$row = [];
		$x->setRow($row);

		foreach ([
			'Title' => 'whatever',
			'CodeName' => __METHOD__,
			'Something' => $this->randomHex(12),
		] as $col => $data) {
			$x->setColumn($col, $data);
		}

		$x->writeRow();

		$x->close();

		$x->headers();

		$this->assertIsString($x->filename());

		$this->assertIsInt($x->rowIndex());
	}

	public function badkeys() {
		return [
			['f!rst'], ['2ECOND'], ['THURD'], ['4'], ['random'], [5], ['5 '], ['six'],
		];
	}

	public function goodkeys() {
		return [
			['first'], ['SECOND'], ['tHIRD'], ['4th'], ['5'],
		];
	}

	/**
	 * @dataProvider badkeys
	 */
	public function test_badkey(string|int $badkey): void {
		$this->expectException(Exception_Key::class);
		$x = new Writer();
		$x->set_headers([
			'First', 'Second', 'Third', '4th', '5', '6',
		], false);

		$x->add_object_map('test', [
			'ID' => $badkey,
		]);
	}

	/**
	 * @dataProvider goodkeys
	 */
	public function test_goodkey(string $key): void {
		$x = new Writer();
		$x->set_headers([
			'First', 'Second', 'Third', '4th', '5', '6.0',
		], false);

		$x->add_object_map('test', [
			'ID' => $key,
		]);
	}
}
