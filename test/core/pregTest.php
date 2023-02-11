<?php
declare(strict_types=1);

namespace zesk;

/**
 *
 */
class pregTest extends UnitTest {
	public function collectIterationKeys(preg $iterator): array {
		$text = $iterator->text();
		$keys = [];
		$i = 0;
		foreach ($iterator as $item) {
			[$_, $key] = $item;
			$keys[] = $key;
			$text = $iterator->replaceCurrent("$i");
			$i++;
		}
		return [$keys, $text];
	}

	public function test_preg(): void {
		$original = '{dog} {fence} {lunch} {dialogue} {Ignored } {Dreadlock}';
		$iterator = preg::matches("/\{([A-Za-z]+)\}/", $original);
		[$keys, $text] = $this->collectIterationKeys($iterator);
		$this->assertEquals(['dog', 'fence', 'lunch', 'dialogue', 'Dreadlock'], $keys);
		$this->assertCount(5, $keys);
		$this->assertEquals('0 1 2 3 {Ignored } 4', $text);

		$this->assertTrue(isset($iterator[0]));
		$this->assertTrue(isset($iterator[1]));
		$this->assertFalse(isset($iterator[99]));

		$this->assertEquals(['{dog}', 'dog'], $iterator[0]);
		$this->assertEquals(['{fence}', 'fence'], $iterator[1]);
		$this->assertEquals(['{Dreadlock}', 'Dreadlock'], $iterator[4]);

		$iterator = preg::matches("/\{([A-Za-z]+)\}/", $original);

		/* Editing, for coverage, not sure of the use cases here */
		$iterator[0] = ['{purple}', 'purple']; // Don't
		unset($iterator[4]);

		[$keys, $text] = $this->collectIterationKeys($iterator);
		$this->assertEquals(['purple', 'fence', 'lunch', 'dialogue'], $keys);
		$this->assertCount(4, $keys);
		$this->assertEquals('0 1 2 3 {Ignored } {Dreadlock}', $text);
	}

	public function test_emptyMatch(): void {
		$original = '{dog} {fence} {lunch} {dialogue} {Ignored } {Dreadlock}';
		$iterator = preg::matches("/\[([A-Za-z]+)\]/", $original);
		[$keys, $text] = $this->collectIterationKeys($iterator);
		$this->assertCount(0, $keys);
		$this->assertEquals($original, $text);
	}

	public static function data_badOffsets(): array {
		return [
			['bad'],
			[-23],
			[-1],
			[6],
		];
	}

	/**
	 * @param $mixed
	 * @return void
	 * @dataProvider data_badOffsets
	 */
	public function test_badOffsets($mixed): void {
		$original = '{dog} {fence} {lunch} {dialogue} {Ignored } {Dreadlock}';
		$iterator = preg::matches("/\{([A-Za-z]+)\}/", $original);
		$this->expectException(Exception_Key::class);
		$iterator[$mixed] = 'problem';
	}
}
