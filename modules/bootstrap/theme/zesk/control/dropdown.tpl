<?php declare(strict_types=1);
/**
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
namespace zesk;

/**
 * Control_Dropdown template render
 */
/* @var $this Template */
if (false) {
	$response = $this->response;
	/* @var $response zesk\Response */
	$object = $this->object;
	/* @var $object Model */
	$widget = $this->widget;
	/* @var $widget Control_Text_Dropdown */
	$name = $this->name;
	/* @var $name string */
	$value = $this->value;
	/* @var $parent Widget */
	$parent = $this->parent;
	/* @var $variables array */
	$variables = $this->variables;
}
$ia = $this->geta('attributes');

$id = $this->id;
$button_id = $this->id . '_button';

$ia['id'] = $button_id;

$class = $this->class;
if ($this->required) {
	$class = CSS::add_class($class, "required");
}
$ia['class'] = CSS::add_class($class, 'form-control');

$ia = $object->apply_map($ia) + [
	'value' => $value,
];

$side = $this->get("dropdown_alignment", "right");

$button_label = "";

$value = $this->value;
if (!$value) {
	$value = $this->default_value;
}
$control_options = $this->control_options;
foreach ($control_options as $code => $attributes) {
	if ($attributes === '-') {
		continue;
	}
	if (!is_array($attributes)) {
		$attributes = [
			'link_html' => $attributes,
		];
	} elseif (!array_key_exists('link_html', $attributes)) {
		$attributes['link_html'] = $code;
	}

	$control_options[$code] = $attributes;
	if ($value === null) {
		$value = $code;
		$button_label = $attributes['link_html'];
	}
	if (!is_array($attributes)) {
		continue;
	}
	if (to_bool(avalue($attributes, 'selected'))) {
		$value = $code;
		$button_label = $attributes['link_html'];
	}
}
$selected = apath($control_options, [
	$value,
	'selected',
]);
if (!$selected) {
	$attributes[$code]['selected'] = true;
}
if (!$button_label) {
	$button_label = apath($control_options, [
		$value,
		"link_html",
	]);
}

$items = [];
foreach ($control_options as $code => $attributes) {
	if ($attributes === '-') {
		$items[] = HTML::tag('li', '.divider', '');
		continue;
	}
	$attributes += [
		'data-value' => $code,
	];
	if (array_key_exists('list_item_attributes', $attributes)) {
		$li_attributes = $attributes['list_item_attributes'];
		unset($attributes['list_item_attributes']);
	} else {
		$li_attributes = [];
	}
	if (array_key_exists('link_html', $attributes)) {
		$link_html = $attributes['link_html'];
		unset($attributes['link_html']);
	} else {
		$link_html = $code;
	}
	if (to_bool(avalue($attributes, 'selected')) || strval($code) === strval($value)) {
		$li_attributes = HTML::add_class($li_attributes, "active");
	}
	$items[] = HTML::tag('li', $li_attributes, HTML::tag('a', $attributes, $link_html));
}

$html = "";

$html .= HTML::div_open([
	'class' => $this->get("outer_class", 'dropdown'), //'input-group-btn'
	'id' => $id,
]);

$input_id = $this->id . "_input";

$html .= HTML::tag('button', [
	'type' => 'button',
	'class' => 'btn btn-default dropdown-toggle',
	'data-toggle' => 'dropdown',
	'id' => $button_id,
	'data-input' => "#$input_id",
	'data-content' => '{label} ',
	'aria-expanded' => 'false',
], HTML::span(".button-label", $button_label) . ' ' . HTML::span('.caret', ''));

$html .= HTML::tag_open('ul', [
	"class" => "dropdown-menu dropdown-menu-$side",
	"role" => "menu",
]);

$html .= implode("\n", $items);
$html .= HTML::tag_close('ul');

$html .= HTML::div_close(); // input-group-btn

$input = HTML::input('hidden', $this->name, $this->value, [
	'id' => $this->id . "_input",
]);

if (!$this->no_input_group) {
	echo HTML::div_open('.input-group');
}
echo $html;
if (!$this->no_input_group) {
	echo HTML::div_close(); // input-group
	echo $input;
} elseif ($parent) {
	$parent->suffix($input, true);
} else {
	echo $input;
}

$response->javascript('/share/bootstrap-x/js/dropdown.js', [
	"share" => true,
]);
$response->jquery(map('$("#{id}").bootstrap_dropdown({ onupdate: {onupdate} });', [
	'id' => $id,
	'onupdate' => $this->onupdate ? $this->onupdate : "null",
]));
