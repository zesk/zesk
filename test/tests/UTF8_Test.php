<?php declare(strict_types=1);
namespace zesk;

class UTF8_Test extends Test_Unit {
	public function test_to_iso8859(): void {
		$mixed = null;
		UTF8::to_iso8859($mixed);
	}

	public function test_from_charset(): void {
		$mixed = null;
		$charset = null;
		UTF8::from_charset($mixed, 'iso-8859-1');
	}
}
