<?php
declare(strict_types=1);

namespace zesk;

/**
 *
 * @author kent
 *
 */
class Domain_Test extends Test_Unit {
	protected array $load_modules = [
		'MySQL',
		'ORM',
	];

	/**
	 *
	 */
	public function cookie_domain_data() {
		return [
			[
				'conversion.kent.glucose',
				'kent.glucose',
			],
			[
				'www.conversionruler.com',
				'conversionruler.com',
			],
			[
				'hello.www.conversionruler.com',
				'conversionruler.com',
			],
			[
				'test.conversionruler.com',
				'conversionruler.com',
			],
			[
				'another-fucking-thing.roi-tracking.com',
				'roi-tracking.com',
			],
			[
				'Hello',
				'hello',
			],
			[
				'joe.com',
				'joe.com',
			],
		];
	}

	/**
	 * @dataProvider cookie_domain_data
	 * @param string $domain
	 * @param string $expected
	 */
	public function test_cookie_domains(string $domain, string $expected): void {
		$cookie_domain = Domain::domain_factory($this->application, $domain)->computeCookieDomain();
		$this->assert_equal($cookie_domain, $expected, "$domain cookie domain => $cookie_domain !== $expected");
	}
}


/**
 * @desc       $URL: https://code.marketacumen.com/zesk/trunk/test/classes/http.inc $
 * @package    zesk
 * @subpackage test
 * @author     Kent Davidson <kent@marketacumen.com>
 * @copyright  Copyright &copy; 2022, Market Acumen, Inc.
 * @deprecated
 */


//require_once ZESK_ROOT . "system/http.inc";

//test_status("URL::is");
//$URL::is_tests = array(
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
//foreach ($URL::is_tests as $u => $r) {
//	$this->assert("URL::is('$u') === " . StringTools::fromBool($r), $u);
//}
//
//test_status("URL::protocol_default_port");
//$this->assert('URL::protocolDefaultPort("hTtP") === 80');
//$this->assert('URL::protocolDefaultPort("http") === 80');
//$this->assert('URL::protocolDefaultPort("HTTP") === 80');
//$this->assert('URL::protocolDefaultPort("hTtPs") === 443');
//$this->assert('URL::protocolDefaultPort("https") === 443');
//$this->assert('URL::protocolDefaultPort("HTTPS") === 443');
//$this->assert('URL::protocolDefaultPort("ftp") === 21');
//$this->assert('URL::protocolDefaultPort("mailto") === 25');
//$this->assert('URL::protocolDefaultPort("file") === false');
//$this->assert('URL::protocolDefaultPort("foo") === false');

//test_status("Domain::domain_factory");
//$this->assert('Domain::domain_factory("www.conversionruler.com") === ".conversionruler.com"');
//$this->assert('Domain::domain_factory("hello.www.conversionruler.com") === ".conversionruler.com"');
//$this->assert('Domain::domain_factory("test.conversionruler.com") === ".conversionruler.com"');
//$this->assert('Domain::domain_factory("another-fucking-thing.roi-tracking.com") === ".roi-tracking.com"');
//$this->assert('Domain::domain_factory("Hello") === ".Hello"');
//$this->assert('Domain::domain_factory("joe.com") === ".joe.com"');

//test_status("URL::unparse");
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
//	$this->assert("URL::is('$u') === " . StringTools::fromBool(is_string($u_final)));
//		$parts = URL::parse($u);
//	if ($u_final === false) {
//		$this->assert($u_final === $parts, $u);
//	} else {
//		$u1 = URL::unparse($parts);
//		$parts1 = URL::parse($u1);
//		$u2 = URL::unparse($parts1);
//		$this->assert("'$u1' === '$u2'", $u);
//		$this->assert("'$u2' === '$u_final'", $u);
//	}
//}
//
//test_status("URL::normalize");
//foreach ($urls as $u => $r) {
//	$this->assert("URL::is('$u') === " . StringTools::fromBool(is_string($r)));
//	if (is_string($r)) { $r = "'$r'"; } else { $r = "false"; }
//	$this->assert("URL::normalize('$u') === $r");
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
//test_status("URL::normalize");
//foreach ($norm_urls as $u => $r) {
//	if (is_string($r)) { $r = "'$r'"; } else { $r = "false"; }
//	$this->assert("URL::is('$u') === " . StringTools::fromBool(is_string($r)));
//	$this->assert("URL::normalize('$u') === $r", URL::normalize($u));
//}
//$URL::left_host_tests = array(
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
//test_status("URL::left_host");
//foreach ($URL::left_host_tests as $u => $r) {
//	if (is_string($r)) { $r = "'$r'"; } else { $r = "false"; }
//	$this->assert("URL::left_host('$u') === $r", URL::left_host($u));
//}
//
//$URL::protocol_tests = array(
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
//test_status("URL::scheme");
//foreach ($URL::protocol_tests as $u => $r) {
//	if (is_string($r)) { $r = "'$r'"; } else { $r = "false"; }
//	$this->assert("URL::scheme('$u') === $r", URL::scheme($u));
//	$this->assert("URL::scheme('$u') === URL::protocol('$u')", URL::scheme($u));
//}
//
//test_status("URL::repair");
//$f = file(ZESK_ROOT . "system/test/data/URL::repair.txt");
//foreach ($f as $lineno => $u) {
//	$u = rtrim($u);
//	$this->assert("URL::repair('".str_replace("'",'\\\'',$u)."') !== false", ($lineno+1) . ": $u");
//	$u = URL::repair($u);
//	$this->assert("URL::normalize('".str_replace("'",'\\\'',$u)."') !== false", ($lineno+1) . ": $u");
//}
