<?php

/**
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
namespace zesk;

/* @var $application Application */
/* @var $current_user User */
/* @var $object ORM */
/* @var $request Request */
/* @var $router Router */
try {
	$url = $router->get_route('edit', $this->list_class, array(
		"id" => $this->object
	));
} catch (Exception_ORM_NotFound $e) {
	$application->logger->error("ORM {list_class} not found {object}", array(
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
echo HTML::div(CSS::add_class('.col-xs-8 col-sm-10 action-edit'), HTML::span($attributes, $object->name));

echo HTML::div(array(
	'class' => 'col-xs-2 col-sm-1 total tip',
	'data-placement' => 'left',
	'title' => $this->get('total_title')
), HTML::etag('span', array(
	"class" => 'badge'
), $object->total));

$url = $router->get_route('delete', $this->list_class, array(
	"id" => $this->object->id
));
echo HTML::div(array(
	'class' => 'col-xs-2 col-sm-1 action-delete'
), $current_user->can("delete", $object) ? HTML::tag('a', array(
	'class' => 'close tip',
	'data-placement' => 'left',
	'title' => __('Delete'),
	'data-modal-url' => $url,
	'data-target' => $this->target
), "&times;") : "");

