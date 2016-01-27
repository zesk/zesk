<?php
class test_http extends Test_Unit {

	function test_tld() {
		$x = null;
		http::tld($x);

		$this->assert('http::tld("conversion.kent.glucose") === ".kent.glucose"');
		$this->assert('http::tld("www.conversionruler.com") === ".conversionruler.com"');
		$this->assert('http::tld("hello.www.conversionruler.com") === ".conversionruler.com"');
		$this->assert('http::tld("test.conversionruler.com") === ".conversionruler.com"');
		$this->assert('http::tld("another-fucking-thing.roi-tracking.com") === ".roi-tracking.com"');
		$this->assert('http::tld("Hello") === ".Hello"');
		$this->assert('http::tld("joe.com") === ".joe.com"');
	}

}


/**
 * @desc       $URL: https://code.marketacumen.com/zesk/trunk/test/classes/http.inc $
 * @package    zesk
 * @subpackage test
 * @author     Kent Davidson <kent@marketacumen.com>
 * @copyright  Copyright &copy; 2011, Market Acumen, Inc.
 * @deprecated
 */


//require_once ZESK_ROOT . "system/http.inc";

//test_status("url::is");
//$url::is_tests = array(
//	"http://localhost/SIMPLE.html" => true,
//	"http://localhost/SIMPLE.html" => true,
//	"https://*12312:asdfasdf@localhost:9293/SIMPLE.html?asdfasdljhalskjdhfasdf=asdgasdf&foo=bar&$20=/" => true,
//	"https://localhost/SIMPLE.html" => true,
//	"http:/localhost/SIMPLE.html" => false,
//	"https:/localhost/SIMPLE.html" => false,
//	"http://john:dude@www.test.com:81/SIMPLE.html" => true,
//	"http:/www.test.com/SIMPLE.html" => false,
//	"http://www.TEST.com/SIMPLE.html?a=b&c=d*&#frag" => true,
//	"http://www.TEST.com:80/SIMPLE.html?a=b&c=d*&#frag" => true,
//	"file:///usr/local/etc/php.ini" => true,
//	"mailto://kent@localhost" => true,
//	"mailto://kent@marketruler.com" => true,
//	"mhtml:///file://C:Documents and SettingsstarvisionMy DocumentsThank You for Your Order - LakeChamplainChocolates_com.mht" => false,
//	"mhtml:///ftp://C:Documents and SettingsstarvisionMy DocumentsThank You for Your Order - LakeChamplainChocolates_com.mht" => false,
//	"mhtml:///http://C:Documents and SettingsstarvisionMy DocumentsThank You for Your Order - LakeChamplainChocolates_com.mht" => false,
//	"mhtml:///https://C:Documents and SettingsstarvisionMy DocumentsThank You for Your Order - LakeChamplainChocolates_com.mht" => false,
//);
//foreach ($url::is_tests as $u => $r) {
//	$this->assert("url::is('$u') === " . str::from_bool($r), $u);
//}
//
//test_status("url::protocol_default_port");
//$this->assert('url::protocol_default_port("hTtP") === 80');
//$this->assert('url::protocol_default_port("http") === 80');
//$this->assert('url::protocol_default_port("HTTP") === 80');
//$this->assert('url::protocol_default_port("hTtPs") === 443');
//$this->assert('url::protocol_default_port("https") === 443');
//$this->assert('url::protocol_default_port("HTTPS") === 443');
//$this->assert('url::protocol_default_port("ftp") === 21');
//$this->assert('url::protocol_default_port("mailto") === 25');
//$this->assert('url::protocol_default_port("file") === false');
//$this->assert('url::protocol_default_port("foo") === false');

//test_status("http::tld");
//$this->assert('http::tld("www.conversionruler.com") === ".conversionruler.com"');
//$this->assert('http::tld("hello.www.conversionruler.com") === ".conversionruler.com"');
//$this->assert('http::tld("test.conversionruler.com") === ".conversionruler.com"');
//$this->assert('http::tld("another-fucking-thing.roi-tracking.com") === ".roi-tracking.com"');
//$this->assert('http::tld("Hello") === ".Hello"');
//$this->assert('http::tld("joe.com") === ".joe.com"');

//test_status("url::unparse");
//
//$urls = array(
//	"http://www.test.com:81/SIMPLE.html" => "http://www.test.com:81/SIMPLE.html",
//	"http://john:dude@www.test.com:81/SIMPLE.html" => "http://john:dude@www.test.com:81/SIMPLE.html",
//	"http:/www.test.com/SIMPLE.html" => false,
//	"http://www.TEST.com/SIMPLE.html?a=b&c=d*&#frag" => "http://www.test.com/SIMPLE.html?a=b&c=d*&#frag",
//	"http://www.TEST.com:80/SIMPLE.html?a=b&c=d*&#frag" => "http://www.test.com/SIMPLE.html?a=b&c=d*&#frag",
//	"file:///usr/local/etc/php.ini" => "file:///usr/local/etc/php.ini",
//	"FTP://Kent:PaSsWoRd@localhost/usr/local/etc/php.ini" => "ftp://Kent:PaSsWoRd@localhost/usr/local/etc/php.ini",
//);
//foreach ($urls as $u => $u_final) {
//	$this->assert("url::is('$u') === " . str::from_bool(is_string($u_final)));
//		$parts = url::parse($u);
//	if ($u_final === false) {
//		$this->assert($u_final === $parts, $u);
//	} else {
//		$u1 = url::unparse($parts);
//		$parts1 = url::parse($u1);
//		$u2 = url::unparse($parts1);
//		$this->assert("'$u1' === '$u2'", $u);
//		$this->assert("'$u2' === '$u_final'", $u);
//	}
//}
//
//test_status("url::normalize");
//foreach ($urls as $u => $r) {
//	$this->assert("url::is('$u') === " . str::from_bool(is_string($r)));
//	if (is_string($r)) { $r = "'$r'"; } else { $r = "false"; }
//	$this->assert("url::normalize('$u') === $r");
//}

//$norm_urls = array(
//	'HTTP://WWW.EXAMPLE.COM/' => "http://www.example.com/",
//	'HTTPS://WWW.EXAMPLE.COM/?test=test' => "https://www.example.com/?test=test",
//	'ftp://USER:PASSWORD@EXAMPLE.COM/' => "ftp://USER:PASSWORD@example.com/",
//	'HTTP://WWW.EXAMPLE.COM' => "http://www.example.com/",
//	'HTTPS://WWW.EXAMPLE.COM?test=test' => 'https://www.example.com/?test=test',
//	'ftp://USER:PASSWORD@EXAMPLE.COM' => "ftp://USER:PASSWORD@example.com/",
//	'FILE://foo' => 'file:///foo',
//	'file:///' => "file:///",
//	'file:///foo' => "file:///foo",
//);
//
//test_status("url::normalize");
//foreach ($norm_urls as $u => $r) {
//	if (is_string($r)) { $r = "'$r'"; } else { $r = "false"; }
//	$this->assert("url::is('$u') === " . str::from_bool(is_string($r)));
//	$this->assert("url::normalize('$u') === $r", url::normalize($u));
//}
//$url::left_host_tests = array(
//	"http://www.test.com:81/SIMPLE.html" => "http://www.test.com:81/",
//	"http://john:dude@www.test.com:81/SIMPLE.html" => "http://john:dude@www.test.com:81/",
//	"http:/www.test.com/SIMPLE.html" => false,
//	"http://www.TEST.com/SIMPLE.html?a=b&c=d*&#frag" => "http://www.test.com/",
//	"http://www.TEST.com:80/SIMPLE.html?a=b&c=d*&#frag" => "http://www.test.com/",
//	"file:///usr/local/etc/php.ini" => false,
//	"FTP://Kent:PaSsWoRd@localhost/usr/local/etc/php.ini" => "ftp://Kent:PaSsWoRd@localhost/",
//	'HTTP://WWW.EXAMPLE.COM/' => "http://www.example.com/",
//	'HTTPS://WWW.EXAMPLE.COM/?test=test' => "https://www.example.com/",
//	'ftp://USER:PASSWORD@EXAMPLE.COM/' => "ftp://USER:PASSWORD@example.com/",
//	'HTTP://WWW.EXAMPLE.COM' => "http://www.example.com/",
//	'HTTPS://WWW.EXAMPLE.COM?test=test' => 'https://www.example.com/',
//	'ftp://USER:PASSWORD@EXAMPLE.COM' => "ftp://USER:PASSWORD@example.com/",
//	'FILE://foo' => false,
//	'file:///' => false,
//	'http://www.example.com:98/path/index.php?id=323&o=123#top' => 'http://www.example.com:98/'
//);
//
//test_status("url::left_host");
//foreach ($url::left_host_tests as $u => $r) {
//	if (is_string($r)) { $r = "'$r'"; } else { $r = "false"; }
//	$this->assert("url::left_host('$u') === $r", url::left_host($u));
//}
//
//$url::protocol_tests = array(
//	'http://www.example.com' => 'http',
//	'https://www.example.com' => 'https',
//	'ftp://www.example.com' => 'ftp',
//	'file://foo' => 'file',
//	'mailto:john@doe.com' => 'mailto',
//	'HTTP://www.example.com' => 'http',
//	'HTTPS://www.example.com' => 'https',
//	'FTP://www.example.com' => 'ftp',
//	'FiLe://foo' => 'file',
//	'MaIlTo:john@doe.com' => 'mailto',
//);
//
//test_status("url::scheme");
//foreach ($url::protocol_tests as $u => $r) {
//	if (is_string($r)) { $r = "'$r'"; } else { $r = "false"; }
//	$this->assert("url::scheme('$u') === $r", url::scheme($u));
//	$this->assert("url::scheme('$u') === url::protocol('$u')", url::scheme($u));
//}
//
//test_status("url::repair");
//$f = file(ZESK_ROOT . "system/test/data/url::repair.txt");
//foreach ($f as $lineno => $u) {
//	$u = rtrim($u);
//	$this->assert("url::repair('".str_replace("'",'\\\'',$u)."') !== false", ($lineno+1) . ": $u");
//	$u = url::repair($u);
//	$this->assert("url::normalize('".str_replace("'",'\\\'',$u)."') !== false", ($lineno+1) . ": $u");
//}
