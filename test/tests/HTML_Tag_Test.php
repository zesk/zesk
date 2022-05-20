<?php declare(strict_types=1);
namespace zesk;

class HTML_Tag_Test extends Test_Unit {
	public function test_basics(): void {
		$name = 'div';
		$attributes = ['class' => 'header'];
		$contents = '<title>Hello</title>';
		$testx = new HTML_Tag($name, $attributes, $contents);

		$testx->contents();

		$testx->inner_html();

		$testx->setInnerHTML(HTML::tag('div', 'hello'));

		$testx->outerHTML();

		$testx->setOuterHTML(HTML::tag('wrap', 'content'));
	}
}
