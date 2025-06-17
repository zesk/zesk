<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk;

use zesk\HTMLTag;
use zesk\PHP;

class HTMLTagTestCase extends TestCase
{
	public function test_basics(): void
	{
		$name = 'div';
		$attributes = ['class' => 'header'];
		$contents = '<title>Hello</title>';
		$tag = new HTMLTag($name, $attributes, $contents);

		$tag->contents();

		$tag->innerHTML();

		$tag->setInnerHTML(HTML::tag('div', [], 'hello'));

		$tag->outerHTML();

		$tag->setOuterHTML(HTML::tag('wrap', [], 'content'));

		PHP::singleton()->settingsOneLine();
		$expected = "new zesk\HTMLTag(\"div\", [\"class\" => \"header\", ], \"<div>hello</div>\", \"<wrap>content</wrap>\", -1)";
		$this->assertEquals($expected, PHP::dump($tag));

		$tag->setContents('Foo');
		$expected = "new zesk\HTMLTag(\"div\", [\"class\" => \"header\", ], \"Foo\", \"<wrap>content</wrap>\", -1)";
		$this->assertEquals($expected, PHP::dump($tag));

		$this->assertEquals('<div class="header">Foo</div>', $tag->__toString());

		$this->assertFalse($tag->isSingle());
		$tag->setContents('');
		$this->assertTrue($tag->isSingle());

		$this->assertEquals('<div class="header" />', $tag->__toString());
	}
}
