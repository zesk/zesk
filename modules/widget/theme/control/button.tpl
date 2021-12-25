<?php declare(strict_types=1);
/**
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
namespace zesk;

/**
 * Bootstrap-themed buttons
 */

/*
 * @var $object Model
 */
$object = $this->object;

$attrs = $this->input_attributes + $this->data_attributes;

$attrs = ArrayTools::clean($attrs, [
	"",
	null,
]);

$button_tag = $this->get('button_tag', "button");
if ($this->href) {
	$button_tag = "a";
	$attrs['href'] = $this->href;
}
$attrs["type"] = $this->getb('submit', true) ? "submit" : "button";
$content = $this->get1("button_label;label", __('OK'));
$attrs['class'] = CSS::add_class('btn', $this->class);
$attrs['name'] = $this->get1('name;column');
$attrs['id'] = $attrs['name'];
if (!isset($attrs['value'])) {
	$attrs['value'] = "1";
}

echo HTML::tag($button_tag, $object->apply_map($attrs), $content);
