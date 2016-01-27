<?php
class test_cdn extends Test_Unit {

	function test_add() {
		$pattern = '/dogfood/';
		$url_prefix = "http://local/path/to/thing/";
		$root_dir = $this->test_sandbox();
		cdn::add($pattern, $url_prefix, $root_dir);

		$this->assert(cdn::url('/dogfood/for/kids.txt') === $url_prefix . "for/kids.txt");

		echo basename(__FILE__) . ": success\n";
	}

	function test_path() {
		$uri = '';
		cdn::path($uri);
	}

	function test_url() {
		$uri = '';
		cdn::url($uri);
	}
}
