<?php declare(strict_types=1);
namespace zesk;

/* @var $object Contact_Email */
$result = $object->theme("view", [
	"show_links" => false,
	'show_other' => false,
]);
echo HTML::strip($result);
