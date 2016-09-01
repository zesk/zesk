<?php
if (false) {
	/* @var $current_user User */
	$current_user = $this->current_user;
	/* @var $object Object */
	$object = $this->object;
	/* @var $request Request */
	$request = $this->request;
	/* @var $router Router */
	$router = $this->router;
}

try {
	$url = $router->get_route('edit', $this->list_class, array(
		"id" => $this->object
	));
} catch (Exception_Object_NotFound $e) {
	log::error("Object {list_class} not found {object}", array(
		"list_class" => $this->list_class,
		"object" => $object->id()
	));
	return;
}

if ($current_user->can("edit", $object)) {
	$attributes = array(
		'data-id' => $object->id(),
		'data-inplace-column' => "name",
		'data-inplace-classes' => "control-list-inplace form-control",
		'data-inplace-url' => $url
	);
} else {
	$attributes = array();
}
echo html::div(css::add_class('.col-xs-8 col-sm-10 action-edit'), html::span($attributes, $object->name));

echo html::div(array(
	'class' => 'col-xs-2 col-sm-1 total tip',
	'data-placement' => 'left',
	'title' => $this->get('total_title')
), html::etag('span', array(
	"class" => 'badge'
), $object->total));

$url = $router->get_route('delete', $this->list_class, array(
	"id" => $this->object->id
));
echo html::div(array(
	'class' => 'col-xs-2 col-sm-1 action-delete'
), $current_user->can("delete", $object) ? html::tag('a', array(
	'class' => 'close tip',
	'data-placement' => 'left',
	'title' => __('Delete'),
	'data-modal-url' => $url,
	'data-target' => $this->target
), "&times;") : "");

