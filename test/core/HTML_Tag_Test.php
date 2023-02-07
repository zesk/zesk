<?php declare(strict_types=1);
namespace zesk;

class HTML_Tag_Test extends UnitTest {
	public function test_basics(): void {
		$name = 'div';
		$attributes = ['class' => 'header'];
		$contents = '<title>Hello</title>';
		$tag = new HTML_Tag($name, $attributes, $contents);

		$tag->contents();

		$tag->innerHTML();

		$tag->setInnerHTML(HTML::tag('div', [], 'hello'));

		$tag->outerHTML();

		$tag->setOuterHTML(HTML::tag('wrap', [], 'content'));

		PHP::singleton()->settingsOneLine();
		$expected = "new zesk\HTML_Tag(\"div\", [\"class\" => \"header\", ], \"<div>hello</div>\", \"<wrap>content</wrap>\", -1)";
		$this->assertEquals($expected, PHP::dump($tag));

		$tag->setContents('Foo');
		$expected = "new zesk\HTML_Tag(\"div\", [\"class\" => \"header\", ], \"Foo\", \"<wrap>content</wrap>\", -1)";
		$this->assertEquals($expected, PHP::dump($tag));

		$this->assertEquals('<div class="header">Foo</div>', $tag->__toString());

		$this->assertFalse($tag->isSingle());
		$tag->setContents('');
		$this->assertTrue($tag->isSingle());

		$this->assertEquals('<div class="header" />', $tag->__toString());
	}
}
