<?php
$uri = url::query_append($this->request->uri(), array(
	"widget::target" => $this->column,
	"ajax" => 1,
	"action" => "selector"
));

$column = $this->column;
$list_id = "$column-control-picker-items";

echo html::tag_open('div', '.control-picker');
echo html::tag('div', '.btn-group', html::tag('a', array(
	'class' => 'btn btn-default',
	'data-modal-url' => $uri,
	'data-target' => "#$list_id"
), $this->get('label_button', html::span('.glyphicon .glyphicon-plus', ''))));

$results = array();
foreach ($this->objects as $object) {
	$results[] = $this->theme($this->theme_item, array(
		"object" => $object,
		"selected" => true
	));
}
// $n = count($results);
// echo html::tag('span', '.badge', __('{n} {nouns} selected', array(
// 	"n" => $n,
// 	"nouns" => lang::plural($this->class_object_name)
// )));


$list_attributes = html::to_attributes($this->list_attributes);
$list_attributes['id'] = $list_id;
$list_attributes = html::add_class($list_attributes, "control-picker-state class-" . strtolower($this->object_class));
if ($this->selectable) {
	$list_attributes = html::add_class($list_attributes, "selectable");
}

echo html::tag($this->get('list_tag', 'div'), $list_attributes, implode("\n", $results));
echo html::tag_close('div');

/* @var $modal_url Module_Modal_URL */
$modal_url = Module::object('modal_url');
if ($modal_url) {
	$modal_url->ready($this->response);
}