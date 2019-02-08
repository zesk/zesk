<?php
namespace zesk;

/* @var $this \zesk\Template */
/* @var $locale \zesk\Locale */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
$uri = URL::query_append($this->request->uri(), array(
	"widget::target" => $this->column,
	"ajax" => 1,
	"action" => "selector",
));

$column = $this->column;
$list_id = "$column-control-picker-items";

echo HTML::tag_open('div', '.control-picker');
echo HTML::tag('div', '.btn-group', HTML::tag('a', array(
	'class' => 'btn btn-default',
	'data-modal-url' => $uri,
	'data-target' => "#$list_id",
), $this->get('label_button', HTML::span('.glyphicon .glyphicon-plus', ''))));

$results = array();
foreach ($this->objects as $object) {
	$results[] = $this->theme($this->theme_item, array(
		"object" => $object,
		"selected" => true,
	));
}
// $n = count($results);
// echo HTML::tag('span', '.badge', __('{n} {nouns} selected', array(
// 	"n" => $n,
// 	"nouns" => $locale->plural($this->class_object_name)
// )));

$list_attributes = HTML::to_attributes($this->list_attributes);
$list_attributes['id'] = $list_id;
$list_attributes = HTML::add_class($list_attributes, "control-picker-state class-" . strtolower($this->object_class));
if ($this->selectable) {
	$list_attributes = HTML::add_class($list_attributes, "selectable");
}

echo HTML::tag($this->get('list_tag', 'div'), $list_attributes, implode("\n", $results));
echo HTML::tag_close('div');

/* @var $modal_url Module_Modal_URL */
try {
	$modal_url = $application->modules->object('modal_url');
	$modal_url->ready($this->response);
} catch (Exception_NotFound $e) {
}
