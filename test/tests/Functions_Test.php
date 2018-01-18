<?php
/**
 * 
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Functions_Test extends Test_Unit {

	function test_path() {
		path();
		
		$this->assert(path("a", "b") === "a/b", path("a", "b") . " !== 'a/b'");
		$this->assert(path("a/", "b") === "a/b", path("a/", "b") . " !== 'a/b'");
		$this->assert(path("a", "/b") === "a/b", path("a", "/b") . " !== 'a/b'");
		$this->assert(path("a/", "/b") === "a/b", path("a/", "b") . " !== 'a/b'");
		$this->assert(path("/a/", "/b") === "/a/b", path("/a/", "/b") . " !== '/a/b'");
		$this->assert(path("/a/", "/b/") === "/a/b/", path("/a/", "/b/") . " !== '/a/b/'");
		$result = path("/a/", "/./", array(
			"/./",
			"////",
			"/././"
		), "/b/");
		$this->assert($result === "/a/b/", $result . " !== '/a/b/'");
		
		$result = path("/publish/nfs/monitor-services", array(
			'control',
			'ruler-reader'
		));
		$this->assert($result === "/publish/nfs/monitor-services/control/ruler-reader", "$result !== /publish/nfs/monitor-services/control/ruler-reader");
	}
	function test_aevalue() {
		$a = array(
			"a" => null,
			"b" => 0,
			"c" => "",
			"d" => array(),
			"e" => "0"
		);
		$ak = array_keys($a);
		foreach ($ak as $k) {
			$this->assert(aevalue($a, $k, "-EMPTY-") === "-EMPTY-", aevalue($a, $k, "-EMPTY-") . " === \"-EMPTY-\"");
		}
		$b = array(
			"a" => "null",
			"b" => "1",
			"c" => " ",
			"d" => array(
				"a"
			)
		);
		foreach ($b as $k => $v) {
			$this->assert_equal(aevalue($b, $k, "-EMPTY-"), $v, _dump(aevalue($b, $k, "-EMPTY-")) . " === " . _dump($v) . " ($k => " . _dump($v) . ")");
		}
	}
	function testStringTools::wrap() {
		$phrase = null;
		StringTools::wrap($phrase);
		
		$this->assert(StringTools::wrap('This is a [simple] example', '<strong>[]</strong>') === 'This is a <strong>simple</strong> example', "'" . StringTools::wrap('This is a [simple] example', '<strong>[]</strong>') . "' === 'This is a <strong>simple</strong> example'");
		
		$this->assert(StringTools::wrap('This is a [1:simple] example', '<strong>[]</strong>') === 'This is a simple example', StringTools::wrap('This is a [1:simple] example', '<strong>[]</strong>') . " === 'This is a simple example'");
		
		$this->assert(StringTools::wrap('This is an example with [two] [items] example', '<strong>[]</strong>', '<em>[]</em>') === 'This is an example with <strong>two</strong> <em>items</em> example');
		
		$this->assert(StringTools::wrap('This is an example with [two] [0:items] example', '<strong>[]</strong>', '<em>[]</em>') === 'This is an example with <strong>two</strong> <strong>items</strong> example');
		
		$this->assert(StringTools::wrap('This is an example with [1:two] [items] example', '<strong>[]</strong>', '<em>[]</em>') === 'This is an example with <em>two</em> <em>items</em> example', StringTools::wrap('This is an example with [1:two] [items] example', '<strong>[]</strong>', '<em>[]</em>') . ' === This is an example with <em>two</em> <em>items</em> example');
		
		$this->assert(StringTools::wrap('This is an example with [1:two] [1:items] example', '<strong>[]</strong>', '<em>[]</em>') === 'This is an example with <em>two</em> <em>items</em> example', StringTools::wrap('This is an example with [1:two] [1:items] example', '<strong>[]</strong>', '<em>[]</em>') . ' === This is an example with <em>two</em> <em>items</em> example');
		
		$this->assert(StringTools::wrap('Nested example with [outernest [nest0] [nest1]] example', '<0>[]</0>', '<1>[]</1>', '<2>[]</2>') === 'Nested example with <2>outernest <0>nest0</0> <1>nest1</1></2> example', StringTools::wrap('Nested example with [outernest [nest0] [nest1]] example', '<0>[]</0>', '<1>[]</1>', '<2>[]</2>') . ' === Nested example with <2>outernest <0>nest0</0> <1>nest1</1></2> example');
	}
	function test_avalue() {
		$a = array();
		$k = "";
		$default = null;
		avalue($a, $k, $default);
		
		$a = array(
			"" => "empty",
			"0" => "zero",
			"A" => "a",
			"B" => "b"
		);
		$this->assert(avalue($a, "") === "empty");
		$this->assert(avalue($a, "z") === null);
		$this->assert(avalue($a, "0") === "zero");
		$this->assert(avalue($a, "A") === "a");
		$this->assert(avalue($a, "a") === null);
		$this->assert(avalue($a, "a", "dude") === "dude");
	}
	function test___() {
		$phrase = null;
		$language = "en";
		__($phrase, $language);
		
		$phrase = null;
		$locale = null;
		__($phrase, $locale);
	}
	function test_theme() {
		$app = $this->application;
		$theme_path = $app->theme_path();
		$type = null;
		$this->assert_equal($app->theme("microsecond", 42.512312), "42.5123");
		$this->assert_equal($app->theme("percent", array(
			42.512312,
			1
		)), "42.5%");
		$this->assert_equal($app->theme("percent", array(
			42.552312,
			1
		)), "42.6%");
		echo $app->theme("percent", array(
			42.552312,
			1
		)) . "\n";
		$this->assert_equal($app->theme("percent", array(
			42.552312,
			0
		)), "43%");
		
		echo $app->theme('control/button', array(
			'label' => 'OK',
			'object' => new Model($this->application)
		));
	}
	function test_dump() {
		$x = null;
		$html = true;
		dump($x, $html);
	}
	function test_backtrace() {
		$doExit = false;
		$n = -1;
		backtrace($doExit, $n);
		backtrace($doExit, 0);
		backtrace($doExit, 1);
		backtrace($doExit, 2);
	}
	function bad_dates() {
		return array(
			''
		);
	}
	function good_dates() {
		return array(
			'2012-10-15',
			'today',
			'next Tuesday',
			'+3 days',
			'October 15th, 2012'
		);
	}
	
	/**
	 * @data_provider good_dates
	 */
	function test_is_date($good_date) {
		$this->assert(is_date($good_date));
	}
	
	/**
	 * @data_provider bad_dates
	 */
	function test_is_date_not($bad_date) {
		$this->assert(!is_date($bad_date));
	}
	function test_is_email() {
		$email = null;
		is_email($email);
		
		$this->assert(is_email('info@conversionruler.com') === true);
		$this->assert(is_email('info@conversionr$uler.com') === false);
		$this->assert(is_email('John-Doe`#arasdf@conversionruler.com') === true);
		$this->assert(is_email('John-Doe`#arasdf@conversionruler.co.uk') === true);
		
		$this->assert(is_email('John-Doe`#arasdf@conversionruler.co') === true);
		$this->assert(is_email('John-Doe`#arasdf@conversionruler.o') === false);
	}
	function test_is_phone() {
		$phone = null;
		is_phone($phone);
		
		$this->assert(is_phone('215-555-1212') === true);
		$this->assert(is_phone('+011 44 23 41 23 23') === true);
		$this->assert(is_phone('+1 215-555-1212') === true);
		$this->assert(is_phone('+1 215-5R55-1212') === false);
		$this->assert(is_phone('(212) 828-4423') === true);
		$this->assert(is_phone('222.333.4444') === true);
	}
	function test_clamp() {
		$minValue = null;
		$value = null;
		$maxValue = null;
		clamp($minValue, $value, $maxValue);
	}
	function test_vartype() {
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
	function test_to_list() {
		$mixed = null;
		$default = null;
		$delimiter = ";";
		to_list($mixed, $default, $delimiter);
	}
	function test_to_integer() {
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
		$this->assert(to_integer(array(), null) === null);
	}
	function test_to_double() {
		$s = null;
		$def = null;
		to_double($s, $def);
		
		$this->assert(to_double(100, null) === 100.0);
		$this->assert(to_double(1, null) === 1.0);
		$this->assert(to_double("10000", null) === 10000.0);
		$this->assert(to_double("-1", null) === -1.0);
		
		$this->assert(to_double("e10000", null) === null);
		$this->assert(to_double(array(), null) === null);
		
		echo basename(__FILE__) . ": success\n";
	}
	function test_to_bool() {
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
		$this->assert(to_bool(array(), null) === null);
		$this->assert(to_bool(new \stdClass(), null) === null);
	}
	static function to_bool_strpos($value, $default = false) {
		if (is_bool($value)) {
			return $value;
		}
		if (!is_scalar($value)) {
			return $default;
		}
		$value = strtolower($value);
		if (strpos(";1;t;y;yes;on;enabled;true;", ";$value;") !== false) {
			return true;
		}
		if (strpos(";0;f;n;no;off;disabled;false;null;", ";$value;") !== false) {
			return false;
		}
		return $default;
	}
	static function to_bool_in_array($value, $default = false) {
		static $tarray = array(
			1,
			't',
			'y',
			'yes',
			'on',
			'enabled',
			'true'
		);
		static $farray = array(
			0,
			'f',
			'n',
			'no',
			'off',
			'disabled',
			'false',
			'null'
		);
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
	function test_to_bool_timing() {
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
	function test_to_array() {
		$mixed = null;
		$default = null;
		to_array($mixed, $default);
		
		$this->assert(to_array("foo") === array(
			"foo"
		));
		$this->assert(to_array(array(
			"foo"
		)) === array(
			"foo"
		));
		$this->assert(to_array(array(
			"foo"
		)) !== array(
			"foob"
		));
		$this->assert(to_array(array(
			1
		)) !== array(
			"1"
		));
		$this->assert(to_array(array(
			1
		)) !== array(
			"1"
		));
		$this->assert(to_array(1) === array(
			1
		));
		$this->assert(to_array("1") === array(
			"1"
		));
		
		echo basename(__FILE__) . ": success\n";
	}
	function test_newline() {
		$set = null;
		newline($set);
		echo basename(__FILE__) . ": success\n";
	}
	function test_map() {
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
		
		$foo = map($test, array());
		
		$sandbox = $this->test_sandbox();
		// file_put_contents("$sandbox/function.map.0.txt", $foo);
		// file_put_contents("$sandbox/function.map.1.txt", $test);
		$this->assert($foo === $test, "Mismatch $foo");
		
		$prefix = "dude";
		$a = array(
			"a" => "b",
			"b" => "c",
			"c" => "d",
			"d" => "e"
		);
		$contents = "{dudea}{a}{dudeb}{b}{duDeC}{c}{dudeD}{d}";
		$v = map($contents, ArrayTools::kprefix($a, $prefix), true);
		$this->assert($v === "b{a}c{b}d{c}e{d}", $v . "=== \"b{a}c{b}d{c}e{d}\"");
		$v = map($contents, ArrayTools::kprefix($a, $prefix), false);
		$this->assert($v === "b{a}c{b}{duDeC}{c}{dudeD}{d}", $v . " === \"b{a}c{b}{duDeC}{c}{dudeD}{d}\"");
	}
	function test_integer_between() {
		$min = null;
		$x = null;
		$max = null;
		integer_between($min, $x, $max);
		
		$this->assert(integer_between(10, 10, 200) === true);
		$this->assert(integer_between(10, 9, 200) === false);
		$this->assert(integer_between(10, array(), 200) === false);
		$this->assert(integer_between(10, 200, 200) === true);
		$this->assert(integer_between(10, 201, 200) === false);
	}
}

