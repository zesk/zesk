<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 * @see classes/functions.php
 * @author kent
 *
 */
class Functions_Test extends Test_Unit {
	public function test_path(): void {
		path();

		$this->assert(path("a", "b") === "a/b", path("a", "b") . " !== 'a/b'");
		$this->assert(path("a/", "b") === "a/b", path("a/", "b") . " !== 'a/b'");
		$this->assert(path("a", "/b") === "a/b", path("a", "/b") . " !== 'a/b'");
		$this->assert(path("a/", "/b") === "a/b", path("a/", "b") . " !== 'a/b'");
		$this->assert(path("/a/", "/b") === "/a/b", path("/a/", "/b") . " !== '/a/b'");
		$this->assert(path("/a/", "/b/") === "/a/b/", path("/a/", "/b/") . " !== '/a/b/'");
		$result = path("/a/", "/./", [
			"/./",
			"////",
			"/././",
		], "/b/");
		$this->assert($result === "/a/b/", $result . " !== '/a/b/'");

		$result = path("/publish/nfs/monitor-services", [
			'control',
			'ruler-reader',
		]);
		$this->assert($result === "/publish/nfs/monitor-services/control/ruler-reader", "$result !== /publish/nfs/monitor-services/control/ruler-reader");
	}

	public function test_aevalue(): void {
		$a = [
			"a" => null,
			"b" => 0,
			"c" => "",
			"d" => [],
			"e" => "0",
		];
		$ak = array_keys($a);
		foreach ($ak as $k) {
			$this->assert(aevalue($a, $k, "-EMPTY-") === "-EMPTY-", aevalue($a, $k, "-EMPTY-") . " === \"-EMPTY-\"");
		}
		$b = [
			"a" => "null",
			"b" => "1",
			"c" => " ",
			"d" => [
				"a",
			],
		];
		foreach ($b as $k => $v) {
			$this->assert_equal(aevalue($b, $k, "-EMPTY-"), $v, _dump(aevalue($b, $k, "-EMPTY-")) . " === " . _dump($v) . " ($k => " . _dump($v) . ")");
		}
	}

	public function test_avalue(): void {
		$a = [];
		$k = "";
		$default = null;
		avalue($a, $k, $default);

		$a = [
			"" => "empty",
			"0" => "zero",
			"A" => "a",
			"B" => "b",
		];
		$this->assert(avalue($a, "") === "empty");
		$this->assert(avalue($a, "z") === null);
		$this->assert(avalue($a, "0") === "zero");
		$this->assert(avalue($a, "A") === "a");
		$this->assert(avalue($a, "a") === null);
		$this->assert(avalue($a, "a", "dude") === "dude");
	}

	public function test___(): void {
		$language = "en";
		$this->assertEquals("one", __("one", $language));

		$this->assertEquals([], __([], $language));
	}

	public function test_theme(): void {
		$app = $this->application;
		$theme_path = $app->theme_path();
		$type = null;
		$this->assert_equal($app->theme("microsecond", 42.512312), "42.5123");
		$this->assert_equal($app->theme("percent", [
			42.512312,
			1,
		]), "42.5%");
		$this->assert_equal($app->theme("percent", [
			42.552312,
			1,
		]), "42.6%");
		echo $app->theme("percent", [
			42.552312,
			1,
		]) . "\n";
		$this->assert_equal($app->theme("percent", [
			42.552312,
			0,
		]), "43%");

		echo $app->theme('control/button', [
			'label' => 'OK',
			'object' => new Model($this->application),
		]);
	}

	public function test_dump(): void {
		$x = null;
		$html = true;
		dump($x, $html);
	}

	public function test_backtrace(): void {
		$doExit = false;
		$n = -1;
		backtrace($doExit, $n);
		backtrace($doExit, 0);
		backtrace($doExit, 1);
		backtrace($doExit, 2);
	}

	public function bad_dates() {
		return [
			'',
		];
	}

	public function good_dates() {
		return [
			'2012-10-15',
			'today',
			'next Tuesday',
			'+3 days',
			'October 15th, 2012',
		];
	}

	/**
	 * @data_provider good_dates
	 */
	public function test_is_date($good_date): void {
		$this->assert(is_date($good_date));
	}

	/**
	 * @data_provider bad_dates
	 */
	public function test_is_date_not($bad_date): void {
		$this->assert(!is_date($bad_date));
	}

	public function is_email_data() {
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

	public function not_is_email_data() {
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
	 * @dataProvider is_email_data
	 */
	public function test_is_email($email): void {
		$this->assertTrue(is_email($email), "is_email($email)");
	}

	/**
	 * @dataProvider not_is_email_data
	 */
	public function test_is_not_email($email): void {
		$this->assertFalse(is_email($email), "is_email($email)");
	}

	public function test_is_phone(): void {
		$phone = null;
		is_phone($phone);

		$this->assert(is_phone('215-555-1212') === true);
		$this->assert(is_phone('+011 44 23 41 23 23') === true);
		$this->assert(is_phone('+1 215-555-1212') === true);
		$this->assert(is_phone('+1 215-5R55-1212') === false);
		$this->assert(is_phone('(212) 828-4423') === true);
		$this->assert(is_phone('222.333.4444') === true);
	}

	public function test_clamp(): void {
		$minValue = null;
		$value = null;
		$maxValue = null;
		clamp($minValue, $value, $maxValue);
	}

	public function test_vartype(): void {
		$this->assert_equal("NULL", type(null));
		$this->assert_equal("stdClass", type(new \stdClass()));
		$this->assert_equal("zesk\\Date", type(new Date()));
		$this->assert_equal("integer", type(223));
		$this->assert_equal("double", type(223.2));
		$this->assert_equal("string", type(""));
		$this->assert_equal("string", type("dude"));
		$this->assert_equal("boolean", type(false));
		$this->assert_equal("boolean", type(true));
	}

	public function test_to_list(): void {
		$mixed = null;
		$default = null;
		$delimiter = ";";
		to_list($mixed, $default, $delimiter);
	}

	public function test_to_integer(): void {
		$s = null;
		$def = null;
		to_integer($s, $def);

		$this->assert(to_integer("124512", null) === 124512);
		$this->assert(to_integer(124512, null) === 124512);
		$this->assert(to_integer("124512.7", null) === 124512);
		$this->assert(to_integer(124512.7, null) === 124512);
		$this->assert(to_integer(124512.999999, null) === 124512);
		$this->assert(to_integer("0.999999", null) === 0);
		$this->assert(to_integer("1.999999", null) === 1);
		$this->assert(to_integer(false, null) === null);
		$this->assert(to_integer(true, null) === null);
		$this->assert(to_integer(true, null) === null);
		$this->assert(to_integer([], null) === null);
	}

	public function test_to_double(): void {
		$s = null;
		$def = null;
		to_double($s, $def);

		$this->assert(to_double(100, null) === 100.0);
		$this->assert(to_double(1, null) === 1.0);
		$this->assert(to_double("10000", null) === 10000.0);
		$this->assert(to_double("-1", null) === -1.0);

		$this->assert(to_double("e10000", null) === null);
		$this->assert(to_double([], null) === null);

		echo basename(__FILE__) . ": success\n";
	}

	public function test_to_bool(): void {
		$this->assert(to_bool(true, null) === true);
		$this->assert(to_bool(1, null) === true);
		$this->assert(to_bool("1", null) === true);
		$this->assert(to_bool("t", null) === true);
		$this->assert(to_bool("T", null) === true);
		$this->assert(to_bool("y", null) === true);
		$this->assert(to_bool("Y", null) === true);
		$this->assert(to_bool("Yes", null) === true);
		$this->assert(to_bool("yES", null) === true);
		$this->assert(to_bool("oN", null) === true);
		$this->assert(to_bool("on", null) === true);
		$this->assert(to_bool("enabled", null) === true);
		$this->assert(to_bool("trUE", null) === true);
		$this->assert(to_bool("true", null) === true);

		$this->assert(to_bool(0, null) === false);
		$this->assert(to_bool("0", null) === false);
		$this->assert(to_bool("f", null) === false);
		$this->assert(to_bool("F", null) === false);
		$this->assert(to_bool("n", null) === false);
		$this->assert(to_bool("N", null) === false);
		$this->assert(to_bool("no", null) === false);
		$this->assert(to_bool("NO", null) === false);
		$this->assert(to_bool("OFF", null) === false);
		$this->assert(to_bool("off", null) === false);
		$this->assert(to_bool("disabled", null) === false);
		$this->assert(to_bool("DISABLED", null) === false);
		$this->assert(to_bool("false", null) === false);
		$this->assert(to_bool("null", null) === false);
		$this->assert(to_bool("", null) === false);

		$this->assert(to_bool("01", null) === null);
		$this->assert(to_bool([], null) === null);
		$this->assert(to_bool(new \stdClass(), null) === null);
	}

	public static function to_bool_strpos($value, $default = false) {
		if (is_bool($value)) {
			return $value;
		}
		if (!is_scalar($value)) {
			return $default;
		}
		$value = strtolower($value);
		if (str_contains(";1;t;y;yes;on;enabled;true;", ";$value;")) {
			return true;
		}
		if (str_contains(";0;f;n;no;off;disabled;false;null;", ";$value;")) {
			return false;
		}
		return $default;
	}

	public static function to_bool_in_array($value, $default = false) {
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
	 * As of 2017-08 the in_array version is nearly identical in speed to the strpos version and varies test-to-test.
	 *
	 * Updated to test for whether it's 10% faster
	 *
	 * @see \to_bool
	 */
	public function test_to_bool_timing(): void {
		$value = null;
		$default = false;
		to_bool($value, $default);
		$t = new Timer();
		for ($i = 0; $i < 100000; $i++) {
			self::to_bool_strpos('true');
			self::to_bool_strpos('false');
		}
		$strpos_timing = $t->elapsed();
		echo "to_bool_strpos: " . $t->elapsed() . "\n";

		$t = new Timer();
		for ($i = 0; $i < 100000; $i++) {
			self::to_bool_in_array('true');
			self::to_bool_in_array('false');
		}
		$in_array_timing = $t->elapsed();
		echo "to_bool_in_array: " . $t->elapsed() . "\n";
		$diff = 20;
		$this->assert($strpos_timing < $in_array_timing * (1 + ($diff / 100)), "strpos to_bool is more than $diff% slower than in_array implementation");
	}

	public function test_to_array(): void {
		$mixed = null;
		$default = null;
		to_array($mixed, $default);

		$this->assert(to_array("foo") === [
			"foo",
		]);
		$this->assert(to_array([
			"foo",
		]) === [
			"foo",
		]);
		$this->assert(to_array([
			"foo",
		]) !== [
			"foob",
		]);
		$this->assert(to_array([
			1,
		]) !== [
			"1",
		]);
		$this->assert(to_array([
			1,
		]) !== [
			"1",
		]);
		$this->assert(to_array(1) === [
			1,
		]);
		$this->assert(to_array("1") === [
			"1",
		]);

		echo basename(__FILE__) . ": success\n";
	}

	public function test_newline(): void {
		$set = null;
		newline($set);
		echo basename(__FILE__) . ": success\n";
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

		$sandbox = $this->test_sandbox();
		// file_put_contents("$sandbox/function.map.0.txt", $foo);
		// file_put_contents("$sandbox/function.map.1.txt", $test);
		$this->assert($foo === $test, "Mismatch $foo");

		$prefix = "dude";
		$a = [
			"a" => "b",
			"b" => "c",
			"c" => "d",
			"d" => "e",
		];
		$contents = "{dudea}{a}{dudeb}{b}{duDeC}{c}{dudeD}{d}";
		$v = map($contents, ArrayTools::kprefix($a, $prefix), true);
		$this->assert($v === "b{a}c{b}d{c}e{d}", $v . "=== \"b{a}c{b}d{c}e{d}\"");
		$v = map($contents, ArrayTools::kprefix($a, $prefix), false);
		$this->assert($v === "b{a}c{b}{duDeC}{c}{dudeD}{d}", $v . " === \"b{a}c{b}{duDeC}{c}{dudeD}{d}\"");
	}

	public function test_integer_between(): void {
		$min = null;
		$x = null;
		$max = null;
		integer_between($min, $x, $max);

		$this->assert(integer_between(10, 10, 200) === true);
		$this->assert(integer_between(10, 9, 200) === false);
		$this->assert(integer_between(10, [], 200) === false);
		$this->assert(integer_between(10, 200, 200) === true);
		$this->assert(integer_between(10, 201, 200) === false);
	}
}
