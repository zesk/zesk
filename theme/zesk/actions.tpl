<?php
/**
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
namespace zesk;

$content = $this->content;
if (!is_array($content)) {
	return;
}
$buttons = array();
$add_ref = $this->getb('add_ref');
foreach ($content as $href => $attrs) {
	if ($add_ref && !URL::has_ref($href)) {
		$href = URL::add_ref($href, $this->ref);
	}
	$tag_attrs = array(
		'href' => $href
	);
	$checkbox = false;
	$type = 'a';
	$class = "btn btn-default";
	if (is_string($attrs)) {
		$title = $attrs;
		$tag_attrs['title'] = $title;
		$input_data_attrs = array();
		$attrs = array(
			'title' => $attrs
		);
	} else {
		$type = avalue($attrs, 'type', $type);
		$checkbox = $type === 'checkbox';
		$input_data_attrs = HTML::input_attributes($attrs) + HTML::data_attributes($attrs);
		unset($widget);
		$title = avalue($attrs, 'title');
		$tag_attrs['title'] = avalue($attrs, 'a_title', $title);
		$class = avalue($attrs, 'class', $class);
		$class = CSS::add_class($class, avalue($attrs, "+class"));
	}
	$visible = avalue($attrs, 'visible', true);
	if (!$visible) {
		continue;
	}
	$tag_attrs['class'] = $class;
	if ($checkbox) {
		$input_data_attrs += array(
			'type' => 'checkbox',
			'value' => 1,
			'checked' => avalue($attrs, 'checked') ? 'checked' : null
		);
		unset($tag_attrs['href']);
		$buttons[] = HTML::tag('label', $tag_attrs, HTML::tag('input', $input_data_attrs) . $title);
	} else {
		$buttons[] = HTML::tag($type, $tag_attrs + $input_data_attrs, $title);
	}
}
echo HTML::etag("div", CSS::add_class(".actions", $this->class), implode("\n", $buttons));
