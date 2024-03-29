<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk;

use stdClass;

/**
 * @see AddressLock/functions.php
 * @author kent
 *
 */
class TypesTest extends UnitTest {
	public function test_patterns(): void {
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

	public function test_theme(): void {
		$themes = $this->application->themes;

		$this->assertEquals('42.5123', $themes->theme('microsecond', [42.512312]));
		$this->assertEquals('42.5%', $themes->theme('percent', [
			42.512312, 1,
		]));
		$this->assertEquals('42.6%', $themes->theme('percent', [
			42.552312, 1,
		]));
		$this->assertEquals('42.6%', $themes->theme('percent', [
			42.552312, 1,
		]));
		$this->assertEquals('43%', $themes->theme('percent', [
			42.552312, 0,
		]));
		$this->assertEquals('43%', $themes->theme('percent', [
			42.552312,
		]));

		$this->assertEquals('1 KB', $themes->theme('bytes', [
			1024,
		]));
	}

	public function test_dump(): void {
		$x = null;
		$html = true;
		ob_start();
		dump($x, $html);
		$dumped = ob_get_clean();
		$this->assertEquals("(null)\n(boolean) true\n", $dumped);
	}

	public function _backtraceLines(int $n): int {
		$content = Kernel::backtrace($n);
		return count(explode("\n", $content));
	}

	public function test_backtrace(): void {
		$this->assertGreaterThan(8, $this->_backtraceLines(-1));
		$this->assertGreaterThan(9, $this->_backtraceLines(0));
		$this->assertEquals(1, $this->_backtraceLines(1));
		$this->assertEquals(2, $this->_backtraceLines(2));
	}

	public static function data_bad_dates(): array {
		return [
			[''],
			[-1],
		];
	}

	public static function data_good_dates(): array {
		return [
			['2012-10-15'], ['today'], ['next Tuesday'], ['+3 days'], ['October 15th, 2012'],
		];
	}

	/**
	 * @dataProvider data_good_dates
	 */
	public function test_is_date($good_date): void {
		$this->assertTrue(Types::isDate($good_date));
	}

	/**
	 * @dataProvider data_bad_dates
	 */
	public function test_is_date_not($bad_date): void {
		$this->assertFalse(Types::isDate($bad_date));
	}

	public static function data_isEmail(): array {
		return [
			[
				'info@conversionruler.com',
			], [
				'John-Doearasdf@conversionruler.com',
			], [
				'John-Doe#arasdf@conversionruler.com',
			], [
				'John-Doe`arasdf@conversionruler.co.uk',
			], [
				'John-Doe`#arasdf@conversionruler.co.uk',
			],
		];
	}

	public static function data_not_is_email(): array {
		return [
			[
				'info@conversion$ruler.com',
			], [
				'.NotAGoodOne@example.com',
			], [
				'NotAGoodOne@-example.com',
			],
		];
	}

	/**
	 * @dataProvider data_isEmail
	 */
	public function test_is_email($email): void {
		$this->assertTrue(Types::isEmail($email), "is_email($email)");
	}

	/**
	 * @dataProvider data_not_is_email
	 */
	public function test_is_not_email($email): void {
		$this->assertFalse(Types::isEmail($email), "is_email($email)");
	}

	public static function data_isPhone(): array {
		return [
			[false, ''], [true, '215-555-1212'], [true, '+011 44 23 41 23 23', ], [true, '+1 215-555-1212'],
			[false, '+1 215-5R55-1212'], [true, '(212) 828-4423'], [true, '222.333.4444'],
		];
	}

	/**
	 * @param bool $expected
	 * @param string $phone
	 * @return void
	 * @dataProvider data_isPhone
	 */
	public function test_isPhone(bool $expected, string $phone): void {
		$this->assertEquals($expected, Types::isPhone($phone), "is_phone(\"$phone\")");
	}

	/**
	 * @param $expected
	 * @param $minValue
	 * @param $value
	 * @param $maxValue
	 * @return void
	 * @dataProvider data_clamp
	 * @todo move to NumberTest.php
	 */
	public function test_clamp($expected, $minValue, $value, $maxValue): void {
		$this->assertEquals($expected, Number::clamp($minValue, $value, $maxValue));
	}

	public static function data_clamp(): array {
		return [
			[0, -1, 0, 1], [-1, -1, -1, 1], [1, -1, 1, 1], [1, -1, 1.0000001, 1], [1, -1, 1e99, 1], [-1, -1, -1e99, 1],
			[-1, -1, -1.0000001, 1], [-0.0000001, -1, -0.0000001, 1], [0.0000001, -1, 0.0000001, 1],
		];
	}

	/**
	 * @param string $expected
	 * @param mixed $mixed
	 * @return void
	 * @dataProvider data_vartype
	 */
	public function test_vartype(string $expected, mixed $mixed): void {
		$this->assertEquals($expected, type($mixed));
	}

	public static function data_vartype(): array {
		return [
			['NULL', null], ['stdClass', new stdClass()], ['zesk\\Date', new Date()], ['integer', 223],
			['double', 223.2], ['string', ''], ['string', 'dude'], ['boolean', false], ['boolean', true],
		];
	}

	public static function data_toList(): array {
		return [
			['1,2,3', [], ',', ['1', '2', '3']],
		];
	}

	/**
	 * @param mixed $mixed
	 * @param array $default
	 * @param string $delimiter
	 * @param array $expected
	 * @return void
	 * @dataProvider data_toList
	 */
	public function test_toList(mixed $mixed, array $default, string $delimiter, array $expected): void {
		$this->assertEquals($expected, Types::toList($mixed, $default, $delimiter));
	}

	/**
	 * @param $expected
	 * @param mixed $mixed
	 * @return void
	 * @dataProvider data_to_integer
	 */
	public function test_to_integer(mixed $mixed, int $expected): void {
		$this->assertEquals($expected, Types::toInteger($mixed));
	}

	public static function data_to_integer(): array {
		return [
			['124512', 124512],
			[124512, 124512],
			['124512.7', 124512],
			[124512.7, 124512],
			[124512.999999, 124512],
			['0.999999', 0],
			['1.999999', 1],
			[false, 0],
			[true, 1],
			[[], 0],
		];
	}

	/**
	 * @param mixed $float_test
	 * @param float $expected
	 * @return void
	 * @dataProvider data_toFloat
	 */
	public function test_toFloat(mixed $float_test, float $expected): void {
		$this->assertEquals($expected, Types::toFloat($float_test));
	}

	public static function data_toFloat(): array {
		return [
			[100, 100.0], [1, 1.0], ['10000', 10000.0], ['-1', -1.0],

			['e10000', 0.0], [[], 0.0],
		];
	}

	/**
	 * @param mixed $test
	 * @param bool|null $expected
	 * @return void
	 * @dataProvider data_toBool
	 */
	public function test_toBool(mixed $test, ?bool $expected): void {
		$this->assertEquals($expected, Types::toBool($test));
	}

	public static function data_toBool(): array {
		return [
			[true, true], [1, true], ['1', true], ['t', true], ['T', true], ['y', true], ['Y', true], ['Yes', true],
			['yES', true], ['oN', true], ['on', true], ['enabled', true], ['enaBLed', true], ['trUE', true],
			['true', true],

			[0, false], ['0', false], ['f', false], ['F', false], ['n', false], ['N', false], ['no', false],
			['NO', false], ['OFF', false], ['off', false], ['disabled', false], ['DISABLED', false],
			['DISaBLED', false], ['false', false], ['null', false], ['', false], ['01', null], [[], null],
			[new stdClass(), true],
		];
	}

	public static function to_bool_strpos($value, $default = false) {
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

	public static function to_bool_in_array($value, $default = false) {
		static $tarray = [
			1, 't', 'y', 'yes', 'on', 'enabled', 'true',
		];
		static $farray = [
			0, 'f', 'n', 'no', 'off', 'disabled', 'false', 'null',
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
	 * As of 2017-08 the in_array version is nearly identical in speed to the strpos version and varies test-to-test.
	 * 2022 in_array in PHP8 is faster, updated toBool wink wink
	 *
	 * Updated to test for whether it's 10% faster
	 *
	 * @see \toBool
	 */
	public function test_to_bool_timing(): void {
		$value = null;
		$default = false;
		toBool($value, $default);
		$t = new Timer();
		for ($i = 0; $i < 100000; $i++) {
			self::to_bool_strpos('true');
			self::to_bool_strpos('false');
		}
		$strpos_timing = $t->elapsed();
		// echo 'to_bool_strpos: ' . $t->elapsed() . "\n";

		$t = new Timer();
		for ($i = 0; $i < 100000; $i++) {
			self::to_bool_in_array('true');
			self::to_bool_in_array('false');
		}
		$in_array_timing = $t->elapsed();
		// echo 'to_bool_in_array: ' . $t->elapsed() . "\n";
		$diff = 20;
		$this->assertLessThan($strpos_timing * (1 + ($diff / 100)), $in_array_timing, "in_array toBool is more than $diff% slower than strpos implementation");
	}

	public function test_toArray(): void {
		$this->assertEquals(toArray('foo'), [
			'foo',
		]);
		$this->assertEquals(toArray([
			'foo',
		]), [
			'foo',
		]);
		$this->assertNotEquals(toArray([
			'foo',
		]), [
			'foob',
		]);
		$this->assertEquals(toArray([
			1,
		]), [
			'1',
		]);
		$this->assertEquals(toArray(1), [
			'1',
		]);
		$this->assertEquals(toArray(1), [
			1,
		]);
		$this->assertEquals(toArray('1'), [
			'1',
		]);
	}

	public function test_map(): void {
		$test = <<<EOF
<html>
<body bgcolor=FFFFFF text=000000>
<HEAD>{base_url}
<TITLE>Thanks for your order!</TITLE>
<META Name="Description" Content=" ">
<META Name="Keywords" Content=" ">
<script language="JavaScript">
<!--

function MM_swapImgRestore()
{
    var i,x,a=document.MM_sr; for(i=0;a&&i<a.length&&(x=a[i])&&x.oSrc;i++) x.src=x.oSrc;
}

function MM_preloadImages()
{
    var d=document;
    if(d.images)
    {
	if(!d.MM_p) d.MM_p=new Array();
	var i,j=d.MM_p.length,a=MM_preloadImages.arguments;
	for(i=0; i<a.length; i++)
	    if (a[i].indexOf("#")!=0)
	    {
		d.MM_p[j]=new Image; d.MM_p[j++].src=a[i];
	    }
    }
}

function MM_swapImage()
{
    var i,j=0,x,a=MM_swapImage.arguments;
    document.MM_sr=new Array;
    for(i=0;i<(a.length-2);i+=3)
	if ((x=MM_findObj(a[i]))!=null){document.MM_sr[j++]=x; if(!x.oSrc) x.oSrc=x.src; x.src=a[i+2];}
}

function MM_openBrWindow(theURL,winName,features)
{
    window.open(theURL,winName,features);
}

function MM_findObj(n, d)
{
    var p,i,x;
    if(!d) d=document; if((p=n.indexOf("?"))>0&&parent.frames.length)
    {
	d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);
    }
EOF;

		$foo = map($test, []);

		//$sandbox = $this->test_sandbox();
		// file_put_contents("$sandbox/function.map.0.txt", $foo);
		// file_put_contents("$sandbox/function.map.1.txt", $test);
		$this->assertEquals($foo, $test);

		$prefix = 'dude';
		$a = [
			'a' => 'b', 'b' => 'c', 'c' => 'd', 'd' => 'e',
		];
		$contents = '{dudea}{a}{dudeb}{b}{duDeC}{c}{dudeD}{d}';
		$v = map($contents, ArrayTools::prefixKeys($a, $prefix), true);
		$this->assertEquals('b{a}c{b}d{c}e{d}', $v);
		$v = map($contents, ArrayTools::prefixKeys($a, $prefix), false);
		$this->assertEquals('b{a}c{b}{duDeC}{c}{dudeD}{d}', $v);
	}

	/**
	 * @return void
	 * @todo move to NumberTest.php
	 */
	public function test_integer_between(): void {
		$this->assertTrue(Number::intBetween(10, 10, 200));
		$this->assertFalse(Number::intBetween(10, 9, 200));
		$this->assertTrue(Number::intBetween(10, 200, 200));
		$this->assertFalse(Number::intBetween(10, 201, 200));
	}
}
