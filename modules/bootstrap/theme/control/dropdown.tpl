<?php

/**
 * Control_Dropdown template render
 */
/* @var $this Template */
if (false) {
	$response = $this->response;
	/* @var $response Response_HTML */
	$object = $this->object;
	/* @var $object Model */
	$widget = $this->widget;
	/* @var $widget Control_Text_Dropdown */
	$name = $this->name;
	/* @var $name string */
	$value = $this->value;
	$variables = $this->variables;
}
$ia = $this->geta('attributes');

$id = $this->id;
$button_id = $this->id . '_button';

$ia['id'] = $button_id;

$class = $this->class;
if ($this->required) {
	$class = css::add_class($class, "required");
}
$ia['class'] = css::add_class($class, 'form-control');

$ia = $object->apply_map($ia) + array(
	'value' => $value
);

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
		$attributes = array(
			'link_html' => $attributes
		);
	} else if (!array_key_exists('link_html', $attributes)) {
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
$selected = apath($control_options, array(
	$value,
	'selected'
));
if (!$selected) {
	$attributes[$code]['selected'] = true;
}
if (!$button_label) {
	$button_label = apath($control_options, array(
		$value,
		"link_html"
	));
}

$items = array();
foreach ($control_options as $code => $attributes) {
	if ($attributes === '-') {
		$items[] = html::tag('li', '.divider', '');
		continue;
	}
	$attributes += array(
		'data-value' => $code
	);
	if (array_key_exists('list_item_attributes', $attributes)) {
		$li_attributes = $attributes['list_item_attributes'];
		unset($attributes['list_item_attributes']);
	} else {
		$li_attributes = array();
	}
	if (array_key_exists('link_html', $attributes)) {
		$link_html = $attributes['link_html'];
		unset($attributes['link_html']);
	} else {
		$link_html = $code;
	}
	if (to_bool(avalue($attributes, 'selected')) || $code === $value) {
		$li_attributes = html::add_class($li_attributes, "active");
	}
	$items[] = html::tag('li', $li_attributes, html::tag('a', $attributes, $link_html));
}

$html = "";

$html .= html::div_open(array(
	'class' => 'input-group-btn',
	'id' => $id
));

$html .= html::tag('button', array(
	'type' => 'button',
	'class' => 'btn btn-default dropdown-toggle',
	'data-toggle' => 'dropdown',
	'id' => $button_id,
	'data-content' => '{label} ',
	'aria-expanded' => 'false'
), html::span(".button-label", $button_label) . ' ' . html::span('.caret', ''));

$html .= html::tag_open('ul', array(
	"class" => "dropdown-menu dropdown-menu-$side",
	"role" => "menu"
));

$html .= implode("\n", $items);
$html .= html::tag_close('ul');

$html .= html::div_close(); // input-group-btn
$html .= html::input('hidden', $this->column, $this->value, array(
	'id' => $this->id . "_input"
));

if (!$this->no_input_group) {
	echo html::div_open('.input-group');
}
echo $html;
if (!$this->no_input_group) {
	echo html::div_close(); // input-group
}

$response->cdn_javascript('/share/bootstrap-x/js/dropdown.js', array(
	"share" => true
));
$response->jquery(map('$("#{id}").bootstrap_dropdown({ onupdate: {onupdate} });', array(
	'id' => $id,
	'onupdate' => $this->onupdate ? $this->onupdate : "null"
)));
