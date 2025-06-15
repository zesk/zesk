<?php
declare(strict_types=1);
/**
 * @package    zesk
 * @subpackage test
 */

namespace testZesk;

use stdClass;

use zesk\Application;
use zesk\ArrayTools;
use zesk\Exception\ProtocolException;
use zesk\PHP;
use zesk\StringTools;
use zesk\Types;

/**
 * @uses   ArrayTools
 * @coversDefaultClass ArrayTools
 */
class ArrayToolsTest extends TestCase {
	
	/**
	 * @return void
	 * @covers ::map
	 */
	public function test_mapDefaults(): void
	{
		// Require defaults to work
		$this->assertEquals('brackets', ArrayTools::map('{what}', ['what' => 'brackets']));
		$this->assertEquals('{what}', ArrayTools::map('{what}', ['What' => 'brackets']));
		$this->assertEquals('bracketsNoCase', ArrayTools::map('{what}', ['What' => 'bracketsNoCase'], true));
	}
	
	
	/**
	 * @param string|array $expected
	 * @param string|array $test
	 * @param array        $map
	 * @param bool         $case
	 * @param string       $prefix
	 * @param string       $suffix
	 * @return void
	 * @dataProvider ArrayToolsData::map
	 * @covers ::map
	 */
	public function test_map(string|array $expected, string|array $test, array $map, bool $case, string $prefix, string $suffix): void
	{
		$this->assertEquals($expected, ArrayTools::map($test, $map, $case, $prefix, $suffix));
	}
	
	
	/**
	 * @dataProvider ArrayToolsData::simplify
	 * @param array $expected
	 * @param array $array
	 * @return void
	 * @throws ProtocolException
	 * @covers ::simplify
	 */
	public function test_simplify(array $expected, array $array): void
	{
		$this->assertEquals($expected, ArrayTools::simplify($array));
	}
	
	/**
	 * @param mixed $expected
	 * @param mixed $mixed
	 * @param array $map
	 * @return void
	 * @dataProvider ArrayToolsData::replaceSubstrings
	 * @covers ::replaceSubstrings
	 */
	public function test_replaceSubstrings(mixed $expected, mixed $mixed, array $map): void
	{
		$this->assertEquals($expected, ArrayTools::replaceSubstrings($mixed, $map));
	}
	
	/**
	 * @param array $array
	 * @param array $expected
	 * @return void
	 * @dataProvider ArrayToolsData::changeValueCase
	 * @covers ::changeValueCase
	 */
	public function test_changeValueCase(array $array, array $expected): void
	{
		$this->assertEquals($expected, ArrayTools::changeValueCase($array));
	}
	
	/**
	 * @param array $array
	 * @param array $expected
	 * @return void
	 * @dataProvider ArrayToolsData::valuesFlipCopy
	 * @covers ::valuesFlipCopy
	 */
	public function test_valuesFlipCopy(array $array, array $expected): void
	{
		$this->assertEquals($expected, ArrayTools::valuesFlipCopy($array));
	}
	
	/**
	 * @param array  $expected
	 * @param array  $array
	 * @param string $prefix
	 * @param string $suffix
	 * @return void
	 * @dataProvider ArrayToolsData::wrap
	 * @covers ::wrapValues
	 */
	public function test_wrap(array $expected, array $array, string $prefix, string $suffix): void
	{
		$this->assertEquals($expected, ArrayTools::wrapValues($array, $prefix, $suffix));
	}
	
	/**
	 * @param array  $expected
	 * @param array  $array
	 * @param string $prefix
	 * @return void
	 * @dataProvider ArrayToolsData::prefixKeys
	 * @covers ::prefixKeys
	 */
	public function test_prefixKeys(array $expected, array $array, string $prefix): void
	{
		$this->assertEquals($expected, ArrayTools::prefixKeys($array, $prefix));
	}
	
	
	/**
	 * @dataProvider ArrayToolsData::suffixValues
	 * @covers       ::suffixValues
	 * @param array  $expected
	 * @param array  $test
	 * @param string $suffix
	 * @return void
	 */
	public function test_suffixValues(array $expected, array $test, string $suffix): void
	{
		$this->assertEquals($expected, ArrayTools::suffixValues($test, $suffix));
	}
	
	/**
	 * @covers ::keysRemove
	 * @covers       ArrayToolsData::keysRemove
	 * @param array $expected
	 * @param array $test
	 * @param array $remove
	 * @return void
	 */
	public function test_keysRemove(array $expected, array $test, array $remove): void
	{
		$this->assertEquals($expected, ArrayTools::keysRemove($test, $remove));
	}
	
	
	/**
	 * @param bool         $expected
	 * @param array        $array
	 * @param string|array $values
	 * @return void
	 * @dataProvider ArrayToolsData::hasAnyValues
	 * @covers ::hasAnyValue
	 */
	public function test_hasAnyValue(bool $expected, array $array, string|array $values): void
	{
		$this->assertEquals($expected, ArrayTools::hasAnyValue($array, $values));
	}
	
	
	/**
	 * @param bool             $expected
	 * @param array            $array
	 * @param string|array|int $keys
	 * @return void
	 * @dataProvider ArrayToolsData::hasAnyKey
	 * @covers ::hasAnyKey
	 */
	public function test_hasAnyKey(bool $expected, array $array, string|array|int $keys): void
	{
		$this->assertEquals($expected, ArrayTools::hasAnyKey($array, $keys));
	}
	
	
	/**
	 * @return void
	 * @covers ::increment
	 */
	public function test_increment(): void
	{
		$arr = [];
		$k = 'item';
		$result = ArrayTools::increment($arr, $k);
		$this->assertEquals(1, $result);
		$this->assertEquals([
			'item' => 1,
		], $arr);
		$result = ArrayTools::increment($arr, $k);
		$this->assertEquals(2, $result);
		$this->assertEquals([
			'item' => 2,
		], $arr);
		$k = 'decimal';
		$result = ArrayTools::increment($arr, $k, 2.1);
		$this->assertEquals(2.1, $result);
		$this->assertEquals([
			'item'    => 2,
			'decimal' => 2.1,
		], $arr);
		$result = ArrayTools::increment($arr, $k, 3.2);
		$this->assertEqualsWithDelta(5.3, $result, 0.000001);
		// 		$this->assertEquals($arr, array(
		// 			"item" => 2,
		// 			"decimal" => 5.3
		// 		));
	}
	
	/**
	 * @param array      $expected
	 * @param array      $test
	 * @param string|int $key
	 * @param array      $other
	 * @param bool       $before
	 * @return void
	 * @covers ::insert
	 * @dataProvider ArrayToolsData::insert
	 */
	public function test_insert(array $expected, array $test, string|int $key, array $other, bool $before): void
	{
		$this->assertEquals($expected, ArrayTools::insert($test, $key, $other, $before));
	}
	
	/**
	 * @param array  $expected
	 * @param array  $test
	 * @param string $prefix
	 * @param string $suffix
	 * @return void
	 * @dataProvider ArrayToolsData::wrapKeys
	 * @covers ::wrapKeys
	 * @return void
	 */
	public function test_wrapKeys(array $expected, array $test, string $prefix, string $suffix): void
	{
		$this->assertEquals($expected, ArrayTools::wrapKeys($test, $prefix, $suffix));
	}
	
	/**
	 * @param array  $expected
	 * @param array  $test
	 * @param string $prefix
	 * @return void
	 * @covers ::keysRemovePrefix
	 * @dataProvider ArrayToolsData::keysRemovePrefix()
	 * @return void
	 */
	public function test_keysRemovePrefix(array $expected, array $test, string $prefix): void
	{
		$this->assertEquals($expected, ArrayTools::keysRemovePrefix($test, $prefix));
	}
	
	/**
	 * @param array  $expected
	 * @param array  $test
	 * @param string $suffix
	 * @return void
	 * @covers ::keysRemoveSuffix
	 * @dataProvider       ArrayToolsData::keysRemoveSuffix
	 */
	public function test_keysRemoveSuffix(array $expected, array $test, string $suffix): void
	{
		$this->assertEquals($expected, ArrayTools::keysRemoveSuffix($test, $suffix));
	}
	
	/**
	 * @param array $array
	 * @param array $key_map
	 * @param array $expected
	 * @return void
	 * @dataProvider ArrayToolsData::keysMap
	 * @covers ::keysMap
	 */
	public function test_keysMap(array $array, array $key_map, array $expected): void
	{
		$this->assertEquals($expected, ArrayTools::keysMap($array, $key_map));
	}
	
	
	/**
	 * @param array  $array
	 * @param string $delim
	 * @param array  $expected
	 * @return void
	 * @dataProvider ArrayToolsData::pairValues
	 * @covers ::pairValues
	 */
	public function test_pairValues(array $array, string $delim, array $expected): void
	{
		$this->assertEquals($expected, ArrayTools::pairValues($array, $delim));
	}
	
	
	/**
	 * @param $array
	 * @param $value_map
	 * @param $expected
	 * @return void
	 * @dataProvider ArrayToolsData::valuesMap
	 */
	public function test_valuesMap($array, $value_map, $expected): void
	{
		$result = ArrayTools::valuesMap($array, $value_map);
		$this->assertEquals($expected, $result);
	}
	
	/**
	 * @param array $expected
	 * @param array $a1
	 * @param array $a2
	 * @return void
	 * @dataProvider ArrayToolsData::merge
	 */
	public function test_merge(array $expected, array $a1, array $a2): void
	{
		$this->assertEquals($expected, ArrayTools::merge($a1, $a2));
	}
	
	/**
	 * @param array  $expected
	 * @param array  $source
	 * @param string $prefix
	 * @return void
	 * @dataProvider ArrayToolsData::prefixValues()
	 * @covers ::prefixValues
	 */
	public function test_prefixValues(array $expected, array $source, string $prefix): void
	{
		$result = ArrayTools::prefixValues($source, $prefix);
		
		$this->assertEquals($expected, $result);
		
	}
	
	/**
	 * @param array  $expected
	 * @param array  $source
	 * @param string $prefix
	 * @return void
	 * @dataProvider ArrayToolsData::valuesRemovePrefix()
	 * @covers ::valuesRemovePrefix
	 */
	public function test_removePrefix(array $expected, array $source, string $prefix): void
	{
		$result = ArrayTools::valuesRemovePrefix($source, $prefix);
		$this->assertEquals($expected, $result);
	}
	
	/**
	 * @param array  $expected
	 * @param array  $source
	 * @param string $suffix
	 * @return void
	 * @dataProvider ArrayToolsData::valuesRemoveSuffix()
	 * @covers ::valuesRemoveSuffix
	 */
	public function test_removeSuffix(array $expected, array $source, string $suffix): void
	{
		$result = ArrayTools::valuesRemoveSuffix($source, $suffix);
		$this->assertEquals($expected, $result);
	}
	
	/**
	 * @dataProvider ArrayToolsData::trim()
	 * @covers ::trim
	 * @param array $expected
	 * @param array $test
	 * @return void
	 */
	public function test_trim(array $expected, array $test): void
	{
		$result = ArrayTools::trim($test);
		$this->assertEquals($expected, $result);
	}
	
	/**
	 * @covers ::transpose
	 * @dataProvider ArrayToolsData::dataTranspose
	 * @param array $expected
	 * @param array $test
	 * @return void
	 */
	public function test_transpose(array $expected, array $test): void
	{
		$result = ArrayTools::transpose($test);
		$this->assertEquals($expected, $result);
		
		$andBack = ArrayTools::transpose($result);
		$this->assertEquals($test, $andBack);
	}
	
	/**
	 * @param array  $expected
	 * @param array  $test
	 * @param string $prefix
	 * @param bool   $caseInsensitive
	 * @return void
	 * @covers ::filterKeyPrefixes
	 * @dataProvider ArrayToolsData::filterKeyPrefixes
	 */
	public function test_filterKeyPrefixes(array $expected, array $test, string $prefix, bool $caseInsensitive): void
	{
		$this->assertEquals($expected, ArrayTools::filterKeyPrefixes($test, $prefix, $caseInsensitive));
	}
	
	/**
	 * @param array        $expected
	 * @param array        $test
	 * @param string|array $include
	 * @return void
	 * @dataProvider ArrayToolsData::filter()
	 */
	public function test_filter(array $expected, array $test, string|array $include): void
	{
		$result = ArrayTools::filter($test, $include);
		$this->assertEquals($expected, $result);
	}
	
	/**
	 * @param array        $expected
	 * @param array|string $test
	 * @param string|array $needles
	 * @return void
	 * @dataProvider ArrayToolsData::find()
	 * @covers
	 */
	public function test_find(array $expected, array|string $test, string|array $needles): void
	{
		$this->assertEquals($expected, ArrayTools::find($test, $needles));
	}
	
	/**
	 * @dataProvider ArrayToolsData::has
	 * @param bool  $expected
	 * @param array $test
	 * @param mixed $has
	 * @covers ::has
	 * @return void
	 */
	public function test_has(bool $expected, array $test, mixed $has): void
	{
		$this->assertEquals($expected, ArrayTools::has($test, $has), "ArrayTools::has(" . PHP::dump($test) . ", " . PHP::dump($has) . ")");
	}
	
	/**
	 * @return void
	 * @covers ::isAssoc
	 */
	public function test_isAssoc(): void
	{
		$array = [
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
		];
		$this->assertFalse(ArrayTools::isAssoc($array));
		$array[-1] = '';
		$this->assertTrue(ArrayTools::isAssoc($array));
		
		$this->assertFalse(ArrayTools::isAssoc(null));
		$this->assertFalse(ArrayTools::isAssoc(false));
		$this->assertFalse(ArrayTools::isAssoc(true));
		$this->assertFalse(ArrayTools::isAssoc(0));
		$this->assertFalse(ArrayTools::isAssoc(1));
		$this->assertFalse(ArrayTools::isAssoc('mixed'));
		$this->assertFalse(ArrayTools::isAssoc(new stdClass()));
		$this->assertFalse(ArrayTools::isAssoc([]));
		$this->assertFalse(ArrayTools::isAssoc([
			'1',
			'3',
		]));
		$this->assertTrue(ArrayTools::isAssoc([
			'1',
			2 => '3',
		]));
		$this->assertTrue(ArrayTools::isAssoc([
			'1',
			2 => '3',
			4,
			5 => 'f',
		]));
		$this->assertFalse(ArrayTools::isAssoc([
			1,
			2,
			3,
			4,
			5,
			9,
		]));
		$this->assertFalse(ArrayTools::isAssoc(array_merge([
			1,
			2,
			3,
			4,
			5,
			9,
		], [
			'a',
			1,
			2,
			3,
			4,
			5,
			9,
		])));
		$faker = new DummyObject();
		$faker->__set('0', 'zero');
		$faker->__set('1', 'one');
		$this->assertFalse(ArrayTools::isAssoc($faker));
	}
	
	
	/**
	 * @param bool  $expected
	 * @param mixed $test
	 * @return void
	 * @covers ::isList
	 * @dataProvider ArrayToolsData::dataIsList()
	 */
	public function test_isList(bool $expected, mixed $test): void
	{
		$this->assertEquals($expected, ArrayTools::isList($test), "Expected ArrayTools::isList failed for " . Types::type($test));
	}
	
	/**
	 * @param mixed $expected
	 * @param array $source
	 * @param array $source_keys
	 * @param mixed $default
	 * @return void
	 * @dataProvider ArrayToolsData::keysFind
	 * @covers ::keysFind
	 */
	public function test_keysFind(mixed $expected, array $source, array $source_keys, mixed $default): void
	{
		$this->assertEquals($expected, ArrayTools::keysFind($source, $source_keys, $default));
	}
	
	
	/**
	 * @param mixed $expected
	 * @param array $array
	 * @param mixed $default
	 * @return void
	 * @dataProvider ArrayToolsData::max
	 */
	public function test_max(mixed $expected, array $array, mixed $default): void
	{
		$this->assertEquals($expected, ArrayTools::max($array, $default));
	}
	
	
	/**
	 * @param array $array
	 * @param mixed $default
	 * @param mixed $expected
	 * @return void
	 * @dataProvider ArrayToolsData::min
	 * @covers ::min
	 */
	public function test_min(array $array, mixed $default, mixed $expected): void
	{
		$this->assertEquals($expected, ArrayTools::min($array, $default));
	}
	
	/**
	 * @return void
	 * @covers ::path
	 */
	public function test_path(): void
	{
		$array = [
			'path' => [
				'to' => [
					'the' => [
						'deep'    => [
							'portion' => 1231242,
						],
						'funzone' => 'pigeon',
					],
				],
			],
		];
		$path = 'path.to.the.deep.portion';
		$path = Types::toList($path, [], '.');
		$default = null;
		$this->assertEquals(1231242, ArrayTools::path($array, $path, $default));
		$path = 'path.to.the.funzone';
		$path = Types::toList($path, [], '.');
		$this->assertEquals('pigeon', ArrayTools::path($array, $path, $default));
		$path = 'path.to.the.funzone.thing';
		$path = Types::toList($path, [], '.');
		$this->assertEquals('uh-uh', ArrayTools::path($array, $path, 'uh-uh'));
	}
	
	/**
	 * @param string|null $expected
	 * @param string      $haystack
	 * @param array       $needles
	 * @return void
	 * @covers ::findInsensitive
	 * @dataProvider ArrayToolsData::findInsensitive
	 */
	public function test_findInsensitive(string|null $expected, string $haystack, array $needles): void
	{
		$this->assertEquals($expected, ArrayTools::findInsensitive($haystack, $needles));
	}
	
	/**
	 * @return void
	 * @covers ::contains
	 */
	public function test_contains(): void
	{
		$needles = [
			'Aa',
			'rathI',
			'lonGs',
			'sentance',
		];
		
		$haystack = 'A rather long sentence';
		$this->assertFalse(StringTools::contains($haystack, $needles));
		
		$haystack = 'A rather long senaatence';
		$this->assertFalse(StringTools::contains($haystack, $needles));
		
		$haystack = 'A rather long sentence rathI';
		$this->assertEquals(1, StringTools::contains($haystack, $needles));
		
		$haystack = 'lonGSA rather long sentence';
		$this->assertFalse(StringTools::contains($haystack, $needles));
		
		$haystack = 'A rather long sentance';
		$this->assertEquals(3, StringTools::contains($haystack, $needles));
	}
	
	
	/**
	 * @param array  $expected
	 * @param array  $actual
	 * @param string $key
	 * @param mixed  $value
	 * @return void
	 * @dataProvider ArrayToolsData::append
	 */
	public function test_append(array $expected, array $actual, string $key, mixed $value): void
	{
		ArrayTools::append($actual, $key, $value);
		$this->assertEquals($expected, $actual);
	}
	
	/**
	 * @return void
	 * @covers ::rtrim
	 */
	public function test_rtrim(): void
	{
		$a = [
			'efgabcdabcdddabad',
			'ABCabcdddd',
			'abcdeddddddddddddd',
		];
		$characterList = 'abcd';
		$this->assertEquals([
			'efg',
			'ABC',
			'abcde',
		], ArrayTools::rtrim($a, $characterList));
	}
	
	/**
	 * @return void
	 * @covers ::clean
	 */
	public function test_clean(): void
	{
		$this->assertEquals([
			2 => false,
			3 => null,
		], ArrayTools::clean([
			'',
			'',
			false,
			null,
		], ['']));
	}
	
	
	/**
	 * @param $expected
	 * @param $array_to_filter
	 * @param $include
	 * @param $exclude
	 * @param $lower
	 * @return void
	 * @dataProvider ArrayToolsData::filterKeys
	 * @covers       ::filterKeys
	 */
	public function test_filterKeys($expected, $array_to_filter, $include, $exclude, $lower): void
	{
		$this->assertEquals($expected, ArrayTools::filterKeys($array_to_filter, $include, $exclude, $lower));
	}
	
	/**
	 * @return void
	 * @covers ::keysLeftTrim
	 */
	public function test_keysLeftTrim(): void
	{
		$characterList = 'a';
		$this->assertEquals([
			'b'     => 'aaaab',
			'AAAAb' => 'AAAAb',
			'baaaa' => 'baaaa',
		], ArrayTools::keysLeftTrim([
			'aaaab' => 'aaaab',
			'AAAAb' => 'AAAAb',
			'baaaa' => 'baaaa',
		], $characterList));
	}
	
	/**
	 * @return void
	 * @covers ::suffixKeys
	 */
	public function test_suffixKeys(): void
	{
		$arr = [
			1       => 'one',
			2       => 'two',
			'three' => 3,
		];
		$str = 'duck';
		$this->assertEquals([
			'1duck'     => 'one',
			'2duck'     => 'two',
			'threeduck' => 3,
		], ArrayTools::suffixKeys($arr, $str));
	}
	
	/**
	 * @return void
	 * @covers ::valuesLeftTrim
	 */
	public function test_valuesLeftTrim(): void
	{
		$characterList = 'aA';
		$this->assertEquals([
			'aaaab' => 'b',
			'AAAAb' => 'b',
			'baaaa' => 'baaaa',
		], ArrayTools::valuesLeftTrim([
			'aaaab' => 'aaaab',
			'AAAAb' => 'AAAAb',
			'baaaa' => 'baaaa',
		], $characterList));
	}
	
	
	/**
	 * @param string $string
	 * @param string $delimiter
	 * @param string $expected
	 * @return void
	 * @dataProvider ArrayToolsData::preg_quote
	 * @covers ::preg_quote
	 */
	public function test_preg_quote(string $string, string $delimiter, string $expected): void
	{
		$this->assertEquals($expected, ArrayTools::preg_quote($string, $delimiter));
	}
	
	
	/**
	 * @param array  $actual
	 * @param string $key
	 * @param mixed  $value
	 * @param array  $expected
	 * @return void
	 * @dataProvider ArrayToolsData::prepend
	 * @covers ::prepend
	 * /
	 */
	public function test_prepend(array $actual, string $key, mixed $value, array $expected): void
	{
		ArrayTools::prepend($actual, $key, $value);
		$this->assertEquals($expected, $actual);
	}
	
	/**
	 * @return void
	 * @covers ::listTrimClean
	 */
	public function test_listTrimClean(): void
	{
		$arr = [
			"    \n\r\n\n\r\t\t\t\thello\t\n\r",
			"\n\n\n\r\t\n",
			' world',
			' ',
			'',
			null,
			false,
		];
		$result = ArrayTools::listTrimClean($arr);
		$this->assertEquals([
			0 => 'hello',
			2 => 'world',
		], $result);
	}
	
	
	/**
	 * @param array $test
	 * @param array $expected
	 * @return void
	 * @dataProvider ArrayToolsData::listTrim
	 * @covers ::listTrim
	 */
	public function test_listTrim(array $test, array $expected): void
	{
		$this->assertEquals($expected, ArrayTools::listTrim($test));
	}
	
	/**
	 * @return void
	 * @covers ::map ::prefixKeys
	 */
	public function testMapAgain(): void
	{
		$test = file_get_contents(Application::zeskPath('tests/test-data/mapData.txt'));
		
		$foo = ArrayTools::map($test, []);
		
		//$sandbox = $this->test_sandbox();
		// file_put_contents("$sandbox /function.map.0 . txt", $foo);
		// file_put_contents("$sandbox /function.map.1 . txt", $test);
		$this->assertEquals($foo, $test);
		
		$prefix = 'dude';
		$a = [
			'a' => 'b',
			'b' => 'c',
			'c' => 'd',
			'd' => 'e',
		];
		$contents = '{dudea}{a}{dudeb}{b}{duDeC}{c}{dudeD}{d}';
		$v = ArrayTools::map($contents, ArrayTools::prefixKeys($a, $prefix), true);
		$this->assertEquals('b{a}c{b}d{c}e{d}', $v);
		$v = ArrayTools::map($contents, ArrayTools::prefixKeys($a, $prefix));
		$this->assertEquals('b{a}c{b}{duDeC}{c}{dudeD}{d}', $v);
	}
}
