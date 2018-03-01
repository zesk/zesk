<?php
namespace zesk;

class URL_Test extends Test_Unit {
	function test_parse() {
		$test_urls = array(
			"scheme://user:pass@host/path?query#fragment" => array(
				"scheme" => "scheme",
				"user" => "user",
				"pass" => "pass",
				"host" => "host",
				"path" => "/path",
				"query" => "query",
				"fragment" => "fragment"
			),
			"http:///list/flush%20mount%20fuel%20cap.htm" => false,
			"mailto:someone@example.com" => array(
				'scheme' => 'mailto',
				'user' => 'someone',
				'host' => 'example.com'
			)
		);
		
		foreach ($test_urls as $url => $result) {
			$x = URL::parse($url);
			if (is_array($result)) {
				$this->assert_arrays_equal($x, $result, $url . " ... " . _dump($x));
				foreach ($result as $k => $v) {
					$this->assert(URL::parse($url, $k) === $v, "URL::parse(\"$url\", \"$k\") !== $v");
				}
			} else {
				$this->assert($result === $x);
			}
		}
	}
	function test_query_append() {
		$u = null;
		$values = null;
		$is_href = false;
		URL::query_append($u, $values, $is_href);
	}
	function test_query_format() {
		$path = null;
		$add = null;
		$remove = null;
		URL::query_format($path, $add, $remove);
	}
	function test_query_iremove() {
		$u = null;
		$names = null;
		URL::query_iremove($u, $names);
	}
	function left_path() {
		$url = "http://www.example.com/path/?_foo=bar&number1=or-number-2";
		$x = URL::left_path($url);
		$r = "http://www.example.com/path/";
		$this->assert($x === $r, "$x === $r");
	}
	function query_remove() {
		$u = 'http://www.example.com/?a=123&b=def&abcDEF=5716928736+5123123&dogfish=HEAD#marker=12&place=51';
		$names = 'marker;a;abcDEF';
		$isHREF = false;
		$result = URL::query_remove($u, $names, $isHREF);
		$test_result = "http://www.example.com/?b=def&dogfish=HEAD#marker=12&place=51";
		$this->assert($result === $test_result, "$result === $test_result");
		
		$u = '?a=123&b=def&abcDEF=5716928736+5123123&dogfish=HEAD#marker=12&place=51';
		$names = 'marker;a;abcDEF';
		$isHREF = false;
		$result = URL::query_remove($u, $names, $isHREF);
		$test_result = "?b=def&dogfish=HEAD#marker=12&place=51";
		$this->assert($result === $test_result, "$result === $test_result");
		
		$u = '?a=123&b=def&abcDEF=5716928736+5123123&dogfish=HEAD#marker=12&place=51';
		$names = 'marker;a;B;abcDEF';
		$isHREF = false;
		$result = URL::query_remove($u, $names, $isHREF);
		$test_result = "?b=def&dogfish=HEAD#marker=12&place=51";
		$this->assert($result === $test_result, "$result === $test_result");
	}
	function test_unparse() {
		$parts = null;
		URL::unparse($parts);
		
		$urls = array(
			"http://www.test.com:81/SIMPLE.html" => "http://www.test.com:81/SIMPLE.html",
			"http://john:dude@www.test.com:81/SIMPLE.html" => "http://john:dude@www.test.com:81/SIMPLE.html",
			"http:/www.test.com/SIMPLE.html" => false,
			"http://www.TEST.com/SIMPLE.html?a=b&c=d*&#frag" => "http://www.test.com/SIMPLE.html?a=b&c=d*&#frag",
			"http://www.TEST.com:80/SIMPLE.html?a=b&c=d*&#frag" => "http://www.test.com/SIMPLE.html?a=b&c=d*&#frag",
			"file:///usr/local/etc/php.ini" => "file:///usr/local/etc/php.ini",
			"FTP://Kent:PaSsWoRd@localhost/usr/local/etc/php.ini" => "ftp://Kent:PaSsWoRd@localhost/usr/local/etc/php.ini"
		);
		foreach ($urls as $u => $u_final) {
			$this->assert_equal(URL::is($u), is_string($u_final), "Is URL failed: $u");
			$parts = URL::parse($u);
			if ($u_final === false) {
				$this->assert($u_final === $parts, $u);
			} else {
				$u1 = URL::unparse($parts);
				$parts1 = URL::parse($u1);
				$u2 = URL::unparse($parts1);
				$this->assert("'$u1' === '$u2'", $u);
				$this->assert("'$u2' === '$u_final'", $u);
			}
		}
	}
	function test_change_host() {
		$url = null;
		$host = null;
		URL::change_host($url, $host);
		
		$url = "http://www.dude.com:423/path/to/some-file.php?id=1452123&q42=53234#hash_mark";
		$this->assert(URL::change_host($url, "new-host") === "http://new-host:423/path/to/some-file.php?id=1452123&q42=53234#hash_mark", URL::change_host($url, "new-host") . "=== http://new-host:423/path/to/some-file.php?id=1452123&q42=53234#hash_mark");
		
		$url = "http://www.dude.com:80/path/to/some-file.php?id=1452123&q42=53234#hash_mark";
		$this->assert(URL::change_host($url, "new-host") === "http://new-host/path/to/some-file.php?id=1452123&q42=53234#hash_mark");
	}
	function test_compute_href() {
		$url = "http://www.example.com/path/to/file.php?query=value&vale1=412#position";
		$href = "another-file.php?foo=bar#place";
		$this->assert(URL::compute_href($url, $href) === "http://www.example.com/path/to/another-file.php?foo=bar#place");
		
		$href = "/another-file.php?foo=bar#place";
		$this->assert(URL::compute_href($url, $href) === "http://www.example.com/another-file.php?foo=bar#place");
		
		$href = "/another-file.php";
		$this->assert(URL::compute_href($url, $href) === "http://www.example.com/another-file.php");
		
		$href = "#frag";
		$this->assert(URL::compute_href($url, $href) === "http://www.example.com/path/to/file.php?query=value&vale1=412#frag");
		
		$href = "?query=fuck#frag";
		$this->assert(URL::compute_href($url, $href) === "http://www.example.com/path/to/file.php?query=fuck#frag");
		
		$href = "?query=fuck";
		$this->assert(URL::compute_href($url, $href) === "http://www.example.com/path/to/file.php?query=fuck");
	}
	function test_host() {
		$url = null;
		$default = false;
		URL::host($url, $default);
	}
	function test_is() {
		$url = null;
		URL::is($url);
		
		$tests = array(
			"http://localhost/SIMPLE.html" => true,
			"http://localhost/SIMPLE.html" => true,
			"https://*12312:asdfasdf@localhost:9293/SIMPLE.html?asdfasdljhalskjdhfasdf=asdgasdf&foo=bar&$20=/" => true,
			"https://localhost/SIMPLE.html" => true,
			"http:/localhost/SIMPLE.html" => false,
			"https:/localhost/SIMPLE.html" => false,
			"http://john:dude@www.test.com:81/SIMPLE.html" => true,
			"http:/www.test.com/SIMPLE.html" => false,
			"http://www.TEST.com/SIMPLE.html?a=b&c=d*&#frag" => true,
			"http://www.TEST.com:80/SIMPLE.html?a=b&c=d*&#frag" => true,
			"file:///usr/local/etc/php.ini" => true,
			"mailto://kent@localhost" => true,
			"ftp://zesktest:hKfas^911@hornet.dreamhost.com/" => true,
			"mailto://kent@marketruler.com" => true,
			"mhtml:///file://C:Documents and SettingsstarvisionMy DocumentsThank You for Your Order - LakeChamplainChocolates_com.mht" => false,
			"mhtml:///ftp://C:Documents and SettingsstarvisionMy DocumentsThank You for Your Order - LakeChamplainChocolates_com.mht" => false,
			"mhtml:///http://C:Documents and SettingsstarvisionMy DocumentsThank You for Your Order - LakeChamplainChocolates_com.mht" => false,
			"mhtml:///https://C:Documents and SettingsstarvisionMy DocumentsThank You for Your Order - LakeChamplainChocolates_com.mht" => false
		);
		foreach ($tests as $u => $r) {
			echo "Testing url: $u\n";
			Debug::output(URL::parse($u));
			$this->assert_equal(URL::is($u), $r, "URL::is($u)");
		}
	}
	function test_is_absolute() {
		$url = null;
		URL::is_absolute($url);
	}
	function test_is_secure() {
		$url = null;
		URL::is_secure($url);
	}
	function test_left() {
		$url = null;
		$part = null;
		URL::left($url, $part);
	}
	function test_left_host() {
		$u = null;
		URL::left_host($u);
		
		$tests = array(
			"http://www.test.com:81/SIMPLE.html" => "http://www.test.com:81/",
			"http://john:dude@www.test.com:81/SIMPLE.html" => "http://john:dude@www.test.com:81/",
			"http:/www.test.com/SIMPLE.html" => false,
			"http://www.TEST.com/SIMPLE.html?a=b&c=d*&#frag" => "http://www.test.com/",
			"http://www.TEST.com:80/SIMPLE.html?a=b&c=d*&#frag" => "http://www.test.com/",
			"file:///usr/local/etc/php.ini" => "file:///",
			"FTP://Kent:PaSsWoRd@localhost/usr/local/etc/php.ini" => "ftp://Kent:PaSsWoRd@localhost/",
			'HTTP://WWW.EXAMPLE.COM/' => "http://www.example.com/",
			'HTTPS://WWW.EXAMPLE.COM/?test=test' => "https://www.example.com/",
			'ftp://USER:PASSWORD@EXAMPLE.COM/' => "ftp://USER:PASSWORD@example.com/",
			'HTTP://WWW.EXAMPLE.COM' => "http://www.example.com/",
			'HTTPS://WWW.EXAMPLE.COM?test=test' => 'https://www.example.com/',
			'ftp://USER:PASSWORD@EXAMPLE.COM' => "ftp://USER:PASSWORD@example.com/",
			'FILE://foo' => "file:///",
			'file:///' => "file:///",
			'http://www.example.com:98/path/index.php?id=323&o=123#top' => 'http://www.example.com:98/'
		);
		
		$this->log("URL::left_host");
		foreach ($tests as $u => $r) {
			$this->assert_equal(URL::left_host($u), $r, "URL::left_host($u)");
		}
	}
	function test_left_path() {
		$u = null;
		URL::left_path($u);
	}
	function test_normalize() {
		$u = null;
		URL::normalize($u);
		
		$urls = array(
			"http://www.test.com:81/SIMPLE.html" => "http://www.test.com:81/SIMPLE.html",
			"http://john:dude@www.test.com:81/SIMPLE.html" => "http://john:dude@www.test.com:81/SIMPLE.html",
			"http:/www.test.com/SIMPLE.html" => false,
			"http://www.TEST.com/SIMPLE.html?a=b&c=d*&#frag" => "http://www.test.com/SIMPLE.html?a=b&c=d*&#frag",
			"http://www.TEST.com:80/SIMPLE.html?a=b&c=d*&#frag" => "http://www.test.com/SIMPLE.html?a=b&c=d*&#frag",
			"file:///usr/local/etc/php.ini" => "file:///usr/local/etc/php.ini",
			"FTP://Kent:PaSsWoRd@localhost/usr/local/etc/php.ini" => "ftp://Kent:PaSsWoRd@localhost/usr/local/etc/php.ini"
		);
		
		foreach ($urls as $u => $r) {
			$this->assert_equal(URL::is($u), is_string($r));
			$this->assert_equal(URL::normalize($u), $r);
		}
		
		$norm_urls = array(
			'HTTP://WWW.EXAMPLE.COM/' => "http://www.example.com/",
			'HTTPS://WWW.EXAMPLE.COM/?test=test' => "https://www.example.com/?test=test",
			'ftp://USER:PASSWORD@EXAMPLE.COM/' => "ftp://USER:PASSWORD@example.com/",
			'HTTP://WWW.EXAMPLE.COM' => "http://www.example.com/",
			'HTTPS://WWW.EXAMPLE.COM?test=test' => 'https://www.example.com/?test=test',
			'ftp://USER:PASSWORD@EXAMPLE.COM' => "ftp://USER:PASSWORD@example.com/",
			'FILE://foo' => 'file:///foo',
			'file:///' => "file:///",
			'file:///foo' => "file:///foo"
		);
		
		$this->log("URL::normalize");
		foreach ($norm_urls as $u => $r) {
			$this->assert_equal(URL::is($u), is_string($r));
			$this->assert_equal(URL::normalize($u), $r, "URL::normalize($u)");
		}
		
		echo basename(__FILE__) . ": success\n";
	}
	function test_protocol_default_port() {
		$x = null;
		URL::protocol_default_port($x);
		
		$this->log("URL::protocol_default_port");
		$this->assert_equal(URL::protocol_default_port("hTtP"), 80);
		$this->assert_equal(URL::protocol_default_port("http"), 80);
		$this->assert_equal(URL::protocol_default_port("HTTP"), 80);
		$this->assert_equal(URL::protocol_default_port("hTtPs"), 443);
		$this->assert_equal(URL::protocol_default_port("https"), 443);
		$this->assert_equal(URL::protocol_default_port("HTTPS"), 443);
		$this->assert_equal(URL::protocol_default_port("ftp"), 21);
		$this->assert_equal(URL::protocol_default_port("mailto"), 25);
		$this->assert_equal(URL::protocol_default_port("file"), false);
		$this->assert_equal(URL::protocol_default_port("foo"), false);
	}
	function test_query() {
		$url = null;
		$default = false;
		URL::query($url, $default);
	}
	function test_query_from_mixed() {
		$url = null;
		$lower = true;
		URL::query_from_mixed($url, $lower);
	}
	function test_query_iparse() {
		$qs = null;
		$name = null;
		$default = null;
		URL::query_iparse($qs, $name, $default);
	}
	function test_query_unparse() {
		$m = array(
			"A" => "A",
			"B" => "C",
			"D" => "E"
		);
		$this->assertEquals("?A=A&B=C&D=E", URL::query_unparse($m));
	}
	function test_remove_password() {
		$this->assert(URL::remove_password("http://joe:password@example.com/") === "http://joe@example.com/");
	}
	function test_normalize1() {
		$u = null;
		URL::repair($u);
		
		$this->log("URL::repair");
		$f = file(ZESK_ROOT . "test/test-data/url-repair.txt");
		foreach ($f as $lineno => $u) {
			$u = rtrim($u);
			$fixu = str_replace("'", '\\\'', $u);
			$this->assert(URL::repair($fixu) !== false, ($lineno + 1) . ": URL::repair failed: $fixu");
			$normu = URL::repair($fixu);
			$this->assert(URL::normalize($normu) !== false, ($lineno + 1) . ": ur::normalize failed: $normu");
		}
	}
	function test_scheme() {
		$url = null;
		$default = false;
		URL::scheme($url, $default);
		
		$tests = array(
			'http://www.example.com' => 'http',
			'https://www.example.com' => 'https',
			'ftp://www.example.com' => 'ftp',
			'file://foo' => 'file',
			'mailto:john@doe.com' => 'mailto',
			'HTTP://www.example.com' => 'http',
			'HTTPS://www.example.com' => 'https',
			'FTP://www.example.com' => 'ftp',
			'FiLe://foo' => 'file',
			'MaIlTo:john@doe.com' => 'mailto',
			'mysql://foo:bar@localhost/db_name?table_prefix=323' => 'mysql'
		);
		
		$this->log("URL::scheme");
		foreach ($tests as $u => $r) {
			if (!is_string($r)) {
				$r = false;
			}
			$this->assert_equal(URL::scheme($u), $r, "URL::scheme($u)");
		}
	}
}
