<?php declare(strict_types=1);
namespace zesk;

class HTML_Tag_Test extends Test_Unit {
	public function test_basics(): void {
		$name = null;
		$attributes = [];
		$contents = false;
		$testx = new HTML_Tag($name, $attributes, $contents);

		$testx->contents();

		$testx->inner_html();

		$contents = null;
		$testx->inner_html($contents);

		$testx->outer_html();

		$testx->outer_html("<tag>");
	}
}
