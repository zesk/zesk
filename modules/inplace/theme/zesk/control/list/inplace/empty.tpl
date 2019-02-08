<?php
namespace zesk;

echo HTML::div('.row', HTML::div('.col-xs-12 action-edit', HTML::span(array(), __('No {list_object_names}.', array(
	"list_object_names" => $this->list_object_names,
)))));
