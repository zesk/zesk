<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk;

use stdClass;
use zesk\Date;
use zesk\Debug;
use zesk\Exception\ParseException;
use zesk\Types;

/**
 * @see    AddressLock/functions.php
 * @author kent
 * @coversDefaultClass \zesk\Types
 */
class TypesTest extends TestCase
{
	/**
	 * @param      $expected
	 * @param      $mixed
	 * @param bool $throw
	 * @return void
	 * @throws ParseException
	 * @dataProvider data_autoType
	 * @covers ::autoType
	 */
	public function test_autoType($expected, $mixed, bool $throw = false): void
	{
		if ($throw) {
			$this->expectException(ParseException::class);
		}
		$this->assertEquals($expected, Types::autoType($mixed, $throw));
	}

	public static function data_autoType(): array
	{
		$stdClass = new stdClass();
		return [
			[
				12093019,
				'12093019',
			],
			[
				0,
				'0',
			],
			[
				1,
				'1',
			],
			[
				8675309,
				'8675309',
			],
			[
				99.999,
				'99.999',
			],
			[
				$stdClass,
				$stdClass,
			],
			[
				null,
				'null',
			],
			[
				'User',
				'User',
			],
			[
				[],
				'[]',
			],
			[
				[
					1,
					2,
					3,
				],
				'[1,2,3]',
			],
			[
				[
					1,
					2,
					3,
				],
				'[1,2,3,]',
				true,
			],
			[
				[
					1,
					2,
					3,
				],
				'{1,2,3}',
				true,
			],
			[
				['hello' => 'world'],
				'{"hello":"world"}',
			],
		];
	}

	/**
	 * @return void
	 */
	public function test_patterns(): void
	{
		for ($i = 0; $i < 255; $i++) {
			$this->assertTrue(preg_match('/^' . Types::PREG_PATTERN_IP4_DIGIT . '$/', strval($i)) !== 0);
		}
		for ($i = -255; $i < 0; $i++) {
			$this->assertFalse(preg_match('/^' . Types::PREG_PATTERN_IP4_DIGIT . '$/', strval($i)) !== 0);
		}
		for ($i = 256; $i < 32767; $i++) {
			$this->assertFalse(preg_match('/^' . Types::PREG_PATTERN_IP4_DIGIT . '$/', strval($i)) !== 0);
		}
		for ($i = 1; $i < 255; $i++) {
			$this->assertTrue(preg_match('/^' . Types::PREG_PATTERN_IP4_DIGIT1 . '$/', strval($i)) !== 0);
		}
		for ($i = -255; $i < 1; $i++) {
			$this->assertFalse(preg_match('/^' . Types::PREG_PATTERN_IP4_DIGIT1 . '$/', strval($i)) !== 0);
		}
		for ($i = 256; $i < 32767; $i++) {
			$this->assertFalse(preg_match('/^' . Types::PREG_PATTERN_IP4_DIGIT1 . '$/', strval($i)) !== 0);
		}
	}

	public function test_dump(): void
	{
		$x = null;
		$html = true;
		ob_start();
		Debug::output($x, $html);
		$dumped = ob_get_clean();
		$this->assertEquals("(null)\n(boolean) true\n", $dumped);
	}

	public static function data_bad_dates(): array
	{
		return [
			[''],
			[-1],
		];
	}

	public static function data_good_dates(): array
	{
		return [
			['2012-10-15'],
			['today'],
			['next Tuesday'],
			['+3 days'],
			['October 15th, 2012'],
		];
	}

	/**
	 * @dataProvider data_good_dates
	 * @covers ::isDate
	 */
	public function test_is_date($good_date): void
	{
		$this->assertTrue(Types::isDate($good_date));
	}

	/**
	 * @dataProvider data_bad_dates
	 * @covers ::isDate
	 */
	public function test_is_date_not($bad_date): void
	{
		$this->assertFalse(Types::isDate($bad_date));
	}

	/**
	 * @dataProvider data_isEmail
	 * @covers ::isEmail
	 */
	public function test_is_email($email): void
	{
		$this->assertTrue(Types::isEmail($email), "is_email($email)");
	}

	public static function data_isEmail(): array
	{
		return [
			[
				'info@conversionruler.com',
			],
			[
				'John-Doearasdf@conversionruler.com',
			],
			[
				'John-Doe#arasdf@conversionruler.com',
			],
			[
				'John-Doe`arasdf@conversionruler.co.uk',
			],
			[
				'John-Doe`#arasdf@conversionruler.co.uk',
			],
		];
	}

	/**
	 * @dataProvider data_not_is_email
	 * @covers ::isEmail
	 */
	public function test_is_not_email($email): void
	{
		$this->assertFalse(Types::isEmail($email), "is_email($email)");
	}

	public static function data_not_is_email(): array
	{
		return [
			[
				'info@conversion$ruler.com',
			],
			[
				'.NotAGoodOne@example.com',
			],
			[
				'NotAGoodOne@-example.com',
			],
		];
	}

	/**
	 * @param bool $expected
	 * @param string $phone
	 * @return void
	 * @dataProvider data_isPhone
	 * @covers ::isPhone
	 */
	public function test_isPhone(bool $expected, string $phone): void
	{
		$this->assertEquals($expected, Types::isPhone($phone), "is_phone(\"$phone\")");
	}

	public static function data_isPhone(): array
	{
		return [
			[
				false,
				'',
			],
			[
				true,
				'215-555-1212',
			],
			[
				true,
				'+011 44 23 41 23 23',
			],
			[
				true,
				'+1 215-555-1212',
			],
			[
				false,
				'+1 215-5R55-1212',
			],
			[
				true,
				'(212) 828-4423',
			],
			[
				true,
				'222.333.4444',
			],
		];
	}

	/**
	 * @param string $expected
	 * @param mixed $mixed
	 * @return void
	 * @dataProvider data_vartype
	 */
	public function test_vartype(string $expected, mixed $mixed): void
	{
		$this->assertEquals($expected, Types::type($mixed));
	}

	public static function data_vartype(): array
	{
		return [
			[
				'NULL',
				null,
			],
			[
				'stdClass',
				new stdClass(),
			],
			[
				'zesk\\Date',
				new Date(),
			],
			[
				'integer',
				223,
			],
			[
				'double',
				223.2,
			],
			[
				'string',
				'',
			],
			[
				'string',
				'dude',
			],
			[
				'boolean',
				false,
			],
			[
				'boolean',
				true,
			],
		];
	}

	public static function data_toList(): array
	{
		return [
			[
				'1,2,3',
				[],
				',',
				[
					'1',
					'2',
					'3',
				],
			],
		];
	}

	/**
	 * @param mixed $mixed
	 * @param array $default
	 * @param string $delimiter
	 * @param array $expected
	 * @return void
	 * @dataProvider data_toList
	 * @covers ::toList
	 */
	public function test_toList(mixed $mixed, array $default, string $delimiter, array $expected): void
	{
		$this->assertEquals($expected, Types::toList($mixed, $default, $delimiter));
	}

	/**
	 * @param mixed $mixed
	 * @param int $expected
	 * @return void
	 * @dataProvider data_to_integer
	 * @covers ::toInteger
	 */
	public function test_to_integer(mixed $mixed, int $expected): void
	{
		$this->assertEquals($expected, Types::toInteger($mixed));
	}

	public static function data_to_integer(): array
	{
		return [
			[
				'124512',
				124512,
			],
			[
				124512,
				124512,
			],
			[
				'124512.7',
				124512,
			],
			[
				124512.7,
				124512,
			],
			[
				124512.999999,
				124512,
			],
			[
				'0.999999',
				0,
			],
			[
				'1.999999',
				1,
			],
			[
				false,
				0,
			],
			[
				true,
				1,
			],
			[
				[],
				0,
			],
		];
	}

	/**
	 * @param mixed $float_test
	 * @param float $expected
	 * @return void
	 * @dataProvider data_toFloat
	 * @covers ::toFloat
	 */
	public function test_toFloat(mixed $float_test, float $expected): void
	{
		$this->assertEquals($expected, Types::toFloat($float_test));
	}

	public static function data_toFloat(): array
	{
		return [
			[
				100,
				100.0,
			],
			[
				1,
				1.0,
			],
			[
				'10000',
				10000.0,
			],
			[
				'-1',
				-1.0,
			],

			[
				'e10000',
				0.0,
			],
			[
				[],
				0.0,
			],
		];
	}

	/**
	 * @param mixed $test
	 * @param bool|null $expected
	 * @return void
	 * @dataProvider data_toBool
	 * @covers ::toBool
	 */
	public function test_toBool(mixed $test, ?bool $expected): void
	{
		$this->assertEquals($expected, Types::toBool($test));
	}

	public static function data_toBool(): array
	{
		return [
			[
				true,
				true,
			],
			[
				1,
				true,
			],
			[
				'1',
				true,
			],
			[
				't',
				true,
			],
			[
				'T',
				true,
			],
			[
				'y',
				true,
			],
			[
				'Y',
				true,
			],
			[
				'Yes',
				true,
			],
			[
				'yES',
				true,
			],
			[
				'oN',
				true,
			],
			[
				'on',
				true,
			],
			[
				'enabled',
				true,
			],
			[
				'enaBLed',
				true,
			],
			[
				'trUE',
				true,
			],
			[
				'true',
				true,
			],

			[
				0,
				false,
			],
			[
				'0',
				false,
			],
			[
				'f',
				false,
			],
			[
				'F',
				false,
			],
			[
				'n',
				false,
			],
			[
				'N',
				false,
			],
			[
				'no',
				false,
			],
			[
				'NO',
				false,
			],
			[
				'OFF',
				false,
			],
			[
				'off',
				false,
			],
			[
				'disabled',
				false,
			],
			[
				'DISABLED',
				false,
			],
			[
				'DISaBLED',
				false,
			],
			[
				'false',
				false,
			],
			[
				'null',
				false,
			],
			[
				'',
				false,
			],
			[
				'01',
				null,
			],
			[
				[],
				null,
			],
			[
				new stdClass(),
				true,
			],
		];
	}

	public static function to_bool_strpos($value, $default = false)
	{
		if (is_bool($value)) {
			return $value;
		}
		if (!is_scalar($value)) {
			return $default;
		}
		$value = strtolower($value);
		if (str_contains(';1;t;y;yes;on;enabled;true;', ";$value;")) {
			return true;
		}
		if (str_contains(';0;f;n;no;off;disabled;false;null;', ";$value;")) {
			return false;
		}
		return $default;
	}

	public static function to_bool_in_array($value, $default = false)
	{
		static $tarray = [
			1,
			't',
			'y',
			'yes',
			'on',
			'enabled',
			'true',
		];
		static $farray = [
			0,
			'f',
			'n',
			'no',
			'off',
			'disabled',
			'false',
			'null',
		];
		if (is_bool($value)) {
			return $value;
		}
		if (!is_scalar($value)) {
			return $default;
		}
		$value = strtolower($value);
		if (in_array($value, $tarray)) {
			return true;
		}
		if (in_array($value, $farray)) {
			return false;
		}
		return $default;
	}

	/**
	 * @return void
	 * @covers ::toArray
	 */
	public function test_toArray(): void
	{
		$this->assertEquals([
			'foo',
		], Types::toArray('foo'));
		$this->assertEquals([
			'foo',
		], Types::toArray([
			'foo',
		]));
		$this->assertNotEquals([
			'foob',
		], Types::toArray([
			'foo',
		]));
		$this->assertEquals([
			'1',
		], Types::toArray([
			1,
		]));
		$this->assertEquals([
			'1',
		], Types::toArray(1));
		$this->assertEquals([
			1,
		], Types::toArray(1));
		$this->assertEquals([
			'1',
		], Types::toArray('1'));
	}

	public static function data_is_email(): array
	{
		return [
			['test@test.com'],
			['test@example.com'],
			['test@[196.12.42.2]'],
			['test@[IPv4::1]'],
		];
	}

	/**
	 * @param $email
	 * @return void
	 * @dataProvider data_is_email
	 * @covers ::isEmail
	 */
	public function testIsEmailAgain($email): void
	{
		$this->assertTrue(Types::isEmail($email), "$email is not an email");
	}
}
