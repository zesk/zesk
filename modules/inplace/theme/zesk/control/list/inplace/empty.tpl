<?php declare(strict_types=1);
namespace zesk;

echo HTML::div('.row', HTML::div('.col-xs-12 action-edit', HTML::span([], __('No {list_object_names}.', [
	"list_object_names" => $this->list_object_names,
]))));
