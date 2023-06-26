<?php
declare(strict_types=1);

namespace zesk;

use zesk\Exception\SemanticsException;
use zesk\Exception\SyntaxException;

class URLTest extends UnitTest {
	public static function data_parse(): array {
		return [
			[
				[
					'scheme' => 'scheme', 'user' => 'user', 'pass' => 'pass', 'host' => 'host', 'path' => '/path',
					'query' => 'query', 'fragment' => 'fragment',
					'url' => 'scheme://user:pass@host/path?query#fragment',
				], 'scheme://user:pass@host/path?query#fragment',
			], [
				false, 'http:///list/flush%20mount%20fuel%20cap.htm',
			], [
				[
					'scheme' => 'mailto', 'user' => 'someone', 'host' => 'example.com',
					'url' => 'mailto:someone@example.com',
				], 'mailto:someone@example.com',
			],
		];
	}

	/**
	 * @param bool|array $expected
	 * @param string $url
	 * @throws SyntaxException
	 *
	 * @dataProvider data_parse
	 */
	public function test_parse(bool|array $expected, string $url): void {
		if ($expected === false) {
			$this->expectException(SyntaxException::class);
		}
		$this->assertEquals($expected, URL::parse($url), $url);
	}

	public static function data_queryAppend(): array {
		return [
			['/path?a=foo', '/path', ['a' => 'foo'], ],
		];
	}

	/**
	 * @dataProvider data_queryAppend
	 * @param string $expected
	 * @param string $url
	 * @param array $values
	 * @return void
	 */
	public function test_queryAppend(string $expected, string $url, array $values): void {
		$this->assertEquals($expected, URL::queryAppend($url, $values));
	}

	public static function data_queryFormat(): array {
		return [
			[
				'/foo?c=three&d=four&a=one&b=two', '/foo?c=three&d=fourish',
				['a' => 'one', 'b' => 'two', 'd' => 'four'], [],
			], [
				'/foo?d=four&a=one&b=two', '/foo?c=three&d=fourish', ['a' => 'one', 'b' => 'two', 'd' => 'four'], ['c'],
			],
		];
	}

	/**
	 * @param $expected
	 * @param $path
	 * @param $add
	 * @param $remove
	 * @dataProvider data_queryFormat
	 */
	public function test_queryFormat($expected, $path, $add, $remove): void {
		$this->assertEquals($expected, URL::queryFormat($path, $add, $remove));
	}

	public static function data_queryKeysRemoveInsensitive(): array {
		return [
			['/foo', '/foo?BAR=one&Loo=Foo&PoP=age', ['bar', 'loo', 'pop']],
			['/foo', '/foo?BAR=one&Loo=Foo&PoP=age', ['BAR', 'LOO', 'Pop']],
		];
	}

	/**
	 * @param string $expected
	 * @param string $url
	 * @param array $names
	 * @dataProvider data_queryKeysRemoveInsensitive
	 */
	public function test_queryKeysRemoveInsensitive(string $expected, string $url, array $names): void {
		$this->assertEquals($expected, URL::queryKeysRemoveInsensitive($url, $names));
	}

	public function test_queryKeysRemove(): void {
		$u = 'http://www.example.com/?a=123&b=def&abcDEF=5716928736+5123123&dogfish=HEAD#marker=12&place=51';
		$names = 'marker;a;abcDEF';
		$result = URL::queryKeysRemove($u, $names);
		$test_result = 'http://www.example.com/?b=def&dogfish=HEAD#marker=12&place=51';
		$this->assertEquals($test_result, $result);

		$u = '?a=123&b=def&abcDEF=5716928736+5123123&dogfish=HEAD#marker=12&place=51';
		$names = 'marker;a;abcDEF';
		$result = URL::queryKeysRemove($u, $names);
		$test_result = '?b=def&dogfish=HEAD#marker=12&place=51';
		$this->assertEquals($test_result, $result);

		$u = '?a=123&b=def&abcDEF=5716928736+5123123&dogfish=HEAD#marker=12&place=51';
		$names = 'marker;a;B;abcDEF';
		$result = URL::queryKeysRemove($u, $names);
		$test_result = '?b=def&dogfish=HEAD#marker=12&place=51';
		$this->assertEquals($test_result, $result);
	}

	public static function data_stringify(): array {
		$rows = [];
		foreach ([
			'http://www.test.com:81/SIMPLE.html' => 'http://www.test.com:81/SIMPLE.html',
			'http://john:dude@www.test.com:81/SIMPLE.html' => 'http://john:dude@www.test.com:81/SIMPLE.html',
			'http:/www.test.com/SIMPLE.html' => false,
			'http://www.TEST.com/SIMPLE.html?a=b&c=d*&#frag' => 'http://www.test.com/SIMPLE.html?a=b&c=d*&#frag',
			'http://www.TEST.com:80/SIMPLE.html?a=b&c=d*&#frag' => 'http://www.test.com/SIMPLE.html?a=b&c=d*&#frag',
			'file:///usr/local/etc/php.ini' => 'file:///usr/local/etc/php.ini',
			'FTP://Kent:PaSsWoRd@localhost/usr/local/etc/php.ini' => 'ftp://Kent:PaSsWoRd@localhost/usr/local/etc/php.ini',
		] as $u => $u_final) {
			$rows[] = [$u_final, $u];
		}
		return $rows;
	}

	/**
	 * @param string|bool $expected
	 * @param string $url
	 * @return void
	 * @throws SyntaxException
	 * @dataProvider data_stringify
	 */
	public function test_stringify(string|bool $expected, string $url): void {
		if ($expected === false) {
			$this->expectException(SyntaxException::class);
		}
		$parts = URL::parse($url);
		$u1 = URL::stringify($parts);
		$parts1 = URL::parse($u1);
		$u2 = URL::stringify($parts1);
		$this->assertEquals($u1, $u2);
		$this->assertEquals($u2, $expected);
	}

	public static function data_change_host(): array {
		return [

			[
				'http://new-host:423/path/to/some-file.php?id=1452123&q42=53234#hash_mark',
				'http://www.dude.com:423/path/to/some-file.php?id=1452123&q42=53234#hash_mark', 'new-host',
			], [
				'http://new-host/path/to/some-file.php?id=1452123&q42=53234#hash_mark',
				'http://www.dude.com:80/path/to/some-file.php?id=1452123&q42=53234#hash_mark', 'new-host',
			],
		];
	}

	/**
	 * @param $expected
	 * @param $url
	 * @param $host
	 * @dataProvider data_change_host
	 */
	public function test_change_host($expected, $url, $host): void {
		$this->assertEquals($expected, URL::changeHost($url, $host));
	}

	public static function data_computeHREF(): array {
		$url = 'http://www.example.com/path/to/file.php?query=value&vale1=412#position';
		return [
			['http://www.example.com/path/to/another-file.php?foo=bar#place', $url, 'another-file.php?foo=bar#place'],
			['http://www.example.com/another-file.php?foo=bar#place', $url, '/another-file.php?foo=bar#place'],
			['http://www.example.com/another-file.php', $url, '/another-file.php'],
			['http://www.example.com/path/to/file.php?query=value&vale1=412#frag', $url, '#frag'],
			['http://www.example.com/path/to/file.php?query=fuck#frag', $url, '?query=fuck#frag'],
			['http://www.example.com/path/to/file.php?query=fuck', $url, '?query=fuck'],
		];
	}

	/**
	 * @param $expected
	 * @param $url
	 * @param $href
	 * @return void
	 * @dataProvider data_computeHREF
	 */
	public function test_computeHREF($expected, $url, $href): void {
		$this->assertEquals($expected, URL::computeHREF($url, $href));
	}

	public static function data_host(): array {
		return [['www.dude.com', 'https://john:doe@www.dude.com:1234/path?query#hash'], ];
	}

	/**
	 * @param $expected
	 * @param $url
	 * @dataProvider data_host
	 */
	public function test_host($expected, $url): void {
		$this->assertEquals($expected, URL::host($url));
	}

	public static function data_is() {
		$input = [
			'http://localhost/SIMPLE.html' => true,
			'https://*12312:asdfasdf@localhost:9293/SIMPLE.html?asdfasdljhalskjdhfasdf=asdgasdf&foo=bar&$20=/' => true,
			'https://localhost/SIMPLE.html' => true, 'http:/localhost/SIMPLE.html' => false,
			'https:/localhost/SIMPLE.html' => false, 'http://notjam:dude@www.test.com:81/SIMPLE.html' => true,
			'http:/www.test.com/SIMPLE.html' => false, 'http://www.TEST.com/SIMPLE.html?a=b&c=d*&#frag' => true,
			'http://www.TEST.com:80/SIMPLE.html?a=b&c=d*&#frag' => true, 'file:///usr/local/etc/php.ini' => true,
			'mailto://donotemail@localhost' => true, 'ftp://zesktest:hKfas^911@hornet.dreamhost.com/' => true,
			'mailto://donotemail@marketacumen.com' => true,
			'mhtml:///file://C:Documents and SettingsstarvisionMy DocumentsThank You for Your Order - LakeChamplainChocolates_com.mht' => false,
			'mhtml:///ftp://C:Documents and SettingsstarvisionMy DocumentsThank You for Your Order - LakeChamplainChocolates_com.mht' => false,
			'mhtml:///http://C:Documents and SettingsstarvisionMy DocumentsThank You for Your Order - LakeChamplainChocolates_com.mht' => false,
			'mhtml:///https://C:Documents and SettingsstarvisionMy DocumentsThank You for Your Order - LakeChamplainChocolates_com.mht' => false,
		];
		$output = [];
		foreach ($input as $url => $expected) {
			$output[] = [$expected, $url];
		}
		return $output;
	}

	/**
	 * @dataProvider data_is
	 */
	public function test_is($expected, $url): void {
		$this->assertEquals($expected, URL::is($url), $url);
	}

	public static function data_isAbsolute(): array {
		return [
			[true, 'https://www.dude.com/whatever'], [true, 'https://www.dude.com/'], [true, 'https://www.dude.com'],
			[true, 'https://localhost'], [true, 'http://localhost'], [false, '//localhost'], [false, '//path'],
			[false, '/path'], [false, '/path?query'],
		];
	}

	/**
	 * @param bool $expected
	 * @param string $url
	 * @dataProvider data_isAbsolute
	 */
	public function test_isAbsolute(bool $expected, string $url): void {
		$this->assertEquals($expected, URL::isAbsolute($url));
	}

	public static function data_isSecure(): array {
		return [
			[true, 'https://www.example.com/'], [false, 'http://www.example.com/'], [true, 'sftp://user:pass@host/'],
			[true, 'ssh://user@host'], [false, 'telnet://user@host'],
		];
	}

	/**
	 * @param bool $expected
	 * @param string $url
	 * @return void
	 * @dataProvider data_isSecure
	 */
	public function test_is_secure(bool $expected, string $url): void {
		$this->assertEquals($expected, URL::isSecure($url));
	}

	public static function data_left(): array {
		$url = 'https://user:password@host:1234/path/to/thing.html?query=1&id=51213#hash';
		return [
			['https://user:password@host:1234/path/to/thing.html?query=1&id=51213#hash', $url, ''],
			['https://user:password@host:1234/path/to/thing.html?query=1&id=51213#hash', $url, 'hash'],
			['https://user:password@host:1234/path/to/thing.html?query=1&id=51213', $url, 'query'],
			['https://user:password@host:1234/path/to/thing.html', $url, 'path'],
			['https://user:password@host:1234/', $url, 'port'], ['https://user:password@host/', $url, 'host'],
			['https://user:password@/', $url, 'pass'], ['https://user@/', $url, 'user'], ['https:///', $url, 'scheme'],
		];
	}

	/**
	 * @param $expected
	 * @param $url
	 * @param $part
	 * @return void
	 * @throws SyntaxException
	 * @dataProvider data_left
	 */
	public function test_left($expected, $url, $part): void {
		$this->assertEquals($expected, URL::left($url, $part));
	}

	public static function data_left_host(): array {
		$tests = [
			'http://www.test.com:81/SIMPLE.html' => 'http://www.test.com:81/',
			'http://john:dude@www.test.com:81/SIMPLE.html' => 'http://john:dude@www.test.com:81/',
			'http:/www.test.com/SIMPLE.html' => false,
			'http://www.TEST.com/SIMPLE.html?a=b&c=d*&#frag' => 'http://www.test.com/',
			'http://www.TEST.com:80/SIMPLE.html?a=b&c=d*&#frag' => 'http://www.test.com/',
			'file:///usr/local/etc/php.ini' => 'file:///',
			'FTP://Kent:PaSsWoRd@localhost/usr/local/etc/php.ini' => 'ftp://Kent:PaSsWoRd@localhost/',
			'HTTP://WWW.EXAMPLE.COM/' => 'http://www.example.com/',
			'HTTPS://WWW.EXAMPLE.COM/?test=test' => 'https://www.example.com/',
			'ftp://USER:PASSWORD@EXAMPLE.COM/' => 'ftp://USER:PASSWORD@example.com/',
			'HTTP://WWW.EXAMPLE.COM' => 'http://www.example.com/',
			'HTTPS://WWW.EXAMPLE.COM?test=test' => 'https://www.example.com/',
			'ftp://USER:PASSWORD@EXAMPLE.COM' => 'ftp://USER:PASSWORD@example.com/', 'FILE://foo' => 'file:///',
			'file:///' => 'file:///',
			'http://www.example.com:98/path/index.php?id=323&o=123#top' => 'http://www.example.com:98/',
		];
		$output = [];
		foreach ($tests as $url => $expected) {
			$output[] = [$expected, $url];
		}
		return $output;
	}

	/**
	 * @dataProvider data_left_host
	 * @param $expected
	 * @param $url
	 * @return void
	 * @throws SyntaxException
	 */
	public function test_left_host($expected, $url): void {
		if ($expected === false) {
			$this->expectException(SyntaxException::class);
		}
		$this->assertEquals($expected, URL::leftHost($url));
	}

	public static function data_left_paths() {
		return [
			[
				'https://pwned.org:443/random/url?query=string#fragment=more-query&not=query',
				'https://pwned.org/random/url',
			], [
				'https://pwned.org:80/random/url?query=string#fragment=more-query&not=query',
				'https://pwned.org:80/random/url',
			], [
				'http://pwned.org:80/random/url?query=string#fragment=more-query&not=query',
				'http://pwned.org/random/url',
			], [
				'https://pwned.org:4123/random/url?query=string#fragment=more-query&not=query',
				'https://pwned.org:4123/random/url',
			], [
				'https://pwned.org:4123/random/url?query=string#fragment=more-query&not=query',
				'https://pwned.org:4123/random/url',
			], [
				'https://pwned.org/random/url/?query=string#fragment=more-query&not=query',
				'https://pwned.org/random/url/',
			], ['http://www.example.com/path/?_foo=bar&number1=or-number-2', 'http://www.example.com/path/', ],
		];
	}

	/**
	 * @dataProvider data_left_paths
	 */
	public function test_left_path($source, $expected): void {
		$this->assertEquals($expected, URL::left_path($source));
	}

	public static function data_normalize(): array {
		$urls = [
			'http://www.test.com:81/SIMPLE.html' => 'http://www.test.com:81/SIMPLE.html',
			'http://john:dude@www.test.com:81/SIMPLE.html' => 'http://john:dude@www.test.com:81/SIMPLE.html',
			'http:/www.test.com/SIMPLE.html' => false,
			'http://www.TEST.com/SIMPLE.html?a=b&c=d*&#frag' => 'http://www.test.com/SIMPLE.html?a=b&c=d*&#frag',
			'http://www.TEST.com:80/SIMPLE.html?a=b&c=d*&#frag' => 'http://www.test.com/SIMPLE.html?a=b&c=d*&#frag',
			'file:///usr/local/etc/php.ini' => 'file:///usr/local/etc/php.ini',
			'FTP://Kent:PaSsWoRd@localhost/usr/local/etc/php.ini' => 'ftp://Kent:PaSsWoRd@localhost/usr/local/etc/php.ini',
		];
		$norm_urls = [
			'HTTP://WWW.EXAMPLE.COM/' => 'http://www.example.com/',
			'HTTPS://WWW.EXAMPLE.COM/?test=test' => 'https://www.example.com/?test=test',
			'ftp://USER:PASSWORD@EXAMPLE.COM/' => 'ftp://USER:PASSWORD@example.com/',
			'HTTP://WWW.EXAMPLE.COM' => 'http://www.example.com/',
			'HTTPS://WWW.EXAMPLE.COM?test=test' => 'https://www.example.com/?test=test',
			'ftp://USER:PASSWORD@EXAMPLE.COM' => 'ftp://USER:PASSWORD@example.com/', 'FILE://foo' => 'file:///foo',
			'file:///' => 'file:///', 'file:///foo' => 'file:///foo',
		];
		$output = [];
		foreach ($urls + $norm_urls as $url => $expected) {
			$output[] = [$expected, $url];
		}
		return $output;
	}

	/**
	 * @param $expected
	 * @param $url
	 * @dataProvider data_normalize
	 */
	public function test_normalize($expected, $url): void {
		if ($expected === false) {
			$this->expectException(SyntaxException::class);
		}
		$this->assertEquals($expected, URL::normalize($url));
	}

	public static function data_protocolPort(): array {
		return [
			[80, 'hTtP'], [80, 'http'], [80, 'HTTP'], [443, 'hTtPs'], [443, 'https'], [443, 'HTTPS'], [21, 'ftp'],
			[25, 'mailto'], [-1, 'file'], [-1, 'foo'], [25, 'MAILTO'],
		];
	}

	/**
	 * @param int $expected
	 * @param string $scheme
	 * @return void
	 * @dataProvider data_protocolPort
	 */
	public function test_protocolPort(int $expected, string $scheme): void {
		$this->assertEquals($expected, URL::protocolPort($scheme));
	}

	public static function data_query(): array {
		return [['a=b&c=d', 'https://user@localhost:123/path-to/things?a=b&c=d#hash'], ];
	}

	/**
	 * @dataProvider data_query
	 */
	public function test_query($expected, $url): void {
		$this->assertEquals($expected, URL::query($url));
	}

	public static function data_queryParseInsensitive(): array {
		return [
			['12345', 'NAME=john&Id=12345', 'Id', 'nope'], ['12345', 'NAME=john&Id=12345', 'id', 'nope'],
			['nope', 'NAME=john&Id=12345', 'Isd', 'nope'], ['john', 'NAME=john&Id=12345', 'name', 'nope'],
			['john', 'NAME=john&Id=12345', 'Name', 'nope'],
		];
	}

	/**
	 * @param $expected
	 * @param $query
	 * @param $name
	 * @param $default
	 * @dataProvider data_queryParseInsensitive
	 */
	public function test_queryParseInsensitive($expected, $query, $name, $default): void {
		$this->assertEquals($expected, URL::queryParseInsensitive($query, $name, $default));
	}

	public function test_queryUnparse(): void {
		$m = ['A' => 'A', 'B' => 'C', 'D' => 'E', ];
		$this->assertEquals('?A=A&B=C&D=E', URL::queryToString($m));
	}

	public static function data_remove_password(): array {
		return [
			['http://joe@example.com/', 'http://joe:password@example.com/'],
			['https://example.com/', 'https://:password@example.com/'],
			['http://example.com/', 'http://:password@example.com/'],
		];
	}

	/**
	 * @param $expected
	 * @param $url
	 * @throws SyntaxException
	 * @dataProvider data_remove_password
	 */
	public function test_remove_password($expected, $url): void {
		$this->assertEquals($expected, URL::removePassword($url));
	}

	public static function data_repair(): array {
		$output = [];
		$f = file(ZESK_ROOT . 'test/test-data/url-repair.txt');
		foreach ($f as $lineno => $u) {
			$output[] = [rtrim($u), $lineno];
		}
		return $output;
	}

	/**
	 * @param $u
	 * @param $lineno
	 * @return void
	 * @throws SyntaxException
	 * @dataProvider data_repair
	 */
	public function test_repair($u, $lineno): void {
		$fixu = str_replace('\'', '\\\'', $u);
		$normu = URL::repair($fixu);
		URL::normalize($normu);
	}

	public static function data_schema(): array {
		$output = [];
		foreach ([
			'http://www.example.com' => 'http', 'https://www.example.com' => 'https',
			'ftp://www.example.com' => 'ftp', 'file://foo' => 'file', 'mailto:john@doe.com' => 'mailto',
			'HTTP://www.example.com' => 'http', 'HTTPS://www.example.com' => 'https',
			'FTP://www.example.com' => 'ftp', 'FiLe://foo' => 'file', 'MaIlTo:john@doe.com' => 'mailto',
			'mysql://foo:bar@localhost/db_name?table_prefix=323' => 'mysql',
		] as $url => $expected) {
			$output[] = [$expected, $url];
		}
		return $output;
	}

	/**
	 * @param $expected
	 * @param $url
	 * @throws SyntaxException
	 * @dataProvider data_schema
	 */
	public function test_scheme($expected, $url): void {
		$this->assertEquals($expected, URL::scheme($url));
	}

	public static function data_makeSecure(): array {
		return [
			['ssh://user@host/', 'telnet://user@host/'], ['sftp://user@host/', 'ftp://user@host/'],
			['https://www.example.com/', 'http://www.example.com/'], [
				'https://auser:password@www.example.com/pathto/gold.gif?query=abc#place',
				'http://auser:password@www.example.com:80/pathto/gold.gif?query=abc#place',
			], ['', 'http://auser:password@www.example.com:442/'], ['ssh://user@host:99/', 'ssh://user@host:99/'],
			['', 'ftp://user@host:29/'],
		];
	}

	/**
	 * @param string $expected
	 * @param string $url
	 * @return void
	 * @throws Exception\SyntaxException
	 * @throws SemanticsException
	 * @dataProvider data_makeSecure
	 */
	public function test_makeSecure(string $expected, string $url): void {
		if ($expected === '') {
			$this->expectException(SemanticsException::class);
		}
		$this->assertEquals($expected, URL::makeSecure($url));
	}

	public static function data_isSameServer(): array {
		return [
			[true, 'http://www.example.com:80/place', 'http://www.example.com/another-place'],
			[false, 'http://www.example.com:80/place', 'http://www.example.com:81/another-place'],
			[false, 'http://www.example.com:80/place', 'http://www.example.com:81/another-place'],
			[true, 'http://www.example.com:80/', 'http://www.example.com:80/'],
			[true, 'http://www.example.com:80/', 'http://www.example.com:80/foo'],
			[true, 'http://www.example.com:80/', 'http://www.example.com:80/foo/bar?query=bar'],
			[true, 'http://www.example.com:80/', 'http://www.example.com:80/foo/bar?query=bar#frag'],
			[true, 'http://www.example.com:80/', 'http://www.example.com/foo/bar?query=bar#frag'],
			[true, 'http://www.example.com:80/', 'http://user@www.example.com/foo/bar?query=bar#frag'],
			[true, 'http://www.example.com:80/', 'http://user:pass@www.example.com/foo/bar?query=bar#frag'],
			[false, 'http://www.example.com:80/', 'http://www.example.com:81/'],
			[false, 'http://www.example.com:80/', 'http://www.example.co:80/'],
			[false, 'http://www.example.com:80/', 'http://www.example.co:80/'],
		];
	}

	/**
	 * @param bool $expected
	 * @param string $serverLeft
	 * @param string $serverRight
	 * @return void
	 * @dataProvider data_isSameServer
	 */
	public function test_isSameServer(bool $expected, string $serverLeft, string $serverRight): void {
		$this->assertEquals($expected, URL::isSameServer($serverLeft, $serverRight), "URL::isSameServer($serverLeft, $serverRight) should be " . JSON::encode($expected));
	}
}
