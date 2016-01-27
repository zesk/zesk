<?php

/**
 * @see Control_Filter_Selector
 */
$widget = $this->widget;
/* @var $widget Control_Filter_Selector */

$object = $this->object;
/* @var $object Model */

$request = $this->request;
/* @var $request Request */

$response = $this->response;
/* @var $response Response_HTML */

/* @var $toggle_mode boolean */
$toggle_mode = $this->toggle_mode;

$button_icon = html::span('.glyphicon glyphicon-filter', '');
$button_text = __("Filters");

$dropdown_items = array();
$ids = array();
$any_active = false;
$has_selector = $request->has($this->name, false);
$active = false;
foreach ($this->widgets as $child) {
	/* @var $child Widget */
	if ($child->option_bool('filter-selector-ignore')) {
		continue;
	}
	if (!$child->is_visible($object)) {
		continue;
	}
	$content = $child->render();
	$id = $child->id();
	if (!empty($id)) {
		$ids[] = "#" . $id;
	}
	$active = $has_selector ? lists::contains($this->value, $id) : $request->has($child->name());
	if ($active) {
		$any_active = true;
	}
	$dropdown_items[] = html::tag('li', array(
		"class" => "filter-item " . ($active ? "active" : "")
	), html::tag('a', array(
		'data-id' => $id
	), $child->label()));
}

echo html::tag_open('div', css::add_class('.filter-selector', $toggle_mode ? "button-mode" : "btn-group menu-mode"));
if ($toggle_mode) {
	echo html::tag('button', array(
		'type' => "button",
		'title' => __('Click to toggle filters for this list'),
		"class" => css::add_class("btn btn-default selector-toggle-mode tip", $active ? "active" : ""),
		"data-target" => implode(",", $ids)
	), $button_icon . ' ' . $button_text);
} else {
	echo html::tag('button', array(
		'type' => "button",
		"class" => "btn btn-default dropdown-toggle tip",
		"data-toggle" => "dropdown",
		"data-container" => "body",
		"data-placement" => "right",
		"title" => __("Show or hide filters for this list")
	), $button_icon . ' ' . $button_text . html::tag('b', '.caret', ''));
}
echo html::tag_open('ul', array(
	"class" => 'dropdown-menu',
	"role" => "menu"
));
$widget->hook("menu_prefix");
echo html::tag('li', array(
	'class' => 'filter-selector-all'
), html::tag('a', array(
	'data-text-show' => __('Hide all'),
	'data-text-hide' => __('Show all')
), __('Show all')));
echo html::tag('li', array(
	'class' => 'divider'
), "");
echo html::tag('li', array(
	'role' => 'presentation',
	'class' => 'dropdown-header'
), __('Show/Hide filter &hellip;'));
echo implode("\n", $dropdown_items);
$widget->hook("menu_suffix");
echo html::tag_close('ul');
$id = $this->id;
echo html::input_hidden($this->name, $this->value, array(
	"id" => $id,
	"class" => "filter-selector-input"
));
echo html::tag_close('div');

$response->cdn_javascript('/share/zesk/js/control/filter/selector.js', array(
	"share" => true
));
