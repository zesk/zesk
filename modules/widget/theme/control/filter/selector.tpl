<?php
/**
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
namespace zesk;

/**
 * @see Control_Filter_Selector
 */
/* @var $this \zesk\Template */
/* @var $application \zesk\Application */
/* @var $locale \zesk\Locale */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
/* @var $current_user \zesk\User */
$widget = $this->widget;
/* @var $widget \Control_Filter_Selector */

$object = $this->object;
/* @var $object Model */

/* @var $toggle_mode boolean */
$toggle_mode = $this->toggle_mode;

$button_icon = HTML::span('.glyphicon glyphicon-filter', '');
$button_text = $locale->__("Filters");

$dropdown_items = array();
$ids = array();
$any_active = false;
$has_selector = $request->has($this->name, false);
$active = false;
foreach ($this->widgets as $index => $child) {
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
	$active = $has_selector ? Lists::contains($this->value, $id) : $request->has($child->name());
	if ($active) {
		$any_active = true;
	}
	$sort_key = $child->label() . "-" . $index;
	$dropdown_items[$sort_key] = HTML::tag('li', array(
		"class" => "filter-item " . ($active ? "active" : ""),
	), HTML::tag('a', array(
		'data-id' => $id,
	), $child->label()));
}

ksort($dropdown_items);

echo HTML::tag_open('div', CSS::add_class('.filter-selector', $toggle_mode ? "button-mode" : "btn-group menu-mode"));
if ($toggle_mode) {
	echo HTML::tag('button', array(
		'type' => "button",
		'title' => $locale->__('Click to toggle filters for this list'),
		"class" => CSS::add_class("btn btn-default selector-toggle-mode tip", $active ? "active" : ""),
		"data-target" => implode(",", $ids),
	), $button_icon . ' ' . $button_text);
} else {
	echo HTML::tag('button', array(
		'type' => "button",
		"class" => "btn btn-default dropdown-toggle tip",
		"data-toggle" => "dropdown",
		"data-container" => "body",
		"data-placement" => "right",
		"title" => $locale->__("Show or hide filters for this list"),
	), $button_icon . ' ' . $button_text . HTML::tag('b', '.caret', ''));
}
echo HTML::tag_open('ul', array(
	"class" => 'dropdown-menu',
	"role" => "menu",
));
$widget->call_hook("menu_prefix");
echo HTML::tag('li', array(
	'class' => 'filter-selector-all',
), HTML::tag('a', array(
	'data-text-show' => $locale->__('Hide all'),
	'data-text-hide' => $locale->__('Show all'),
), $locale->__('Show all')));
echo HTML::tag('li', array(
	'class' => 'divider',
), "");
echo HTML::tag('li', array(
	'role' => 'presentation',
	'class' => 'dropdown-header',
), $locale->__('Show/Hide filter &hellip;'));
echo implode("\n", $dropdown_items);
$widget->call_hook("menu_suffix");
echo HTML::tag_close('ul');
$id = $this->id;
echo HTML::input_hidden($this->name, $this->value, array(
	"id" => $id,
	"class" => "filter-selector-input",
));
echo HTML::tag_close('div');

$response->javascript('/share/zesk/js/control-filter-selector.js', array(
	"share" => true,
));
