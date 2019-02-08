<?php
namespace zesk;

/* @var $object Contact_Email */
$result = $object->theme("view", array(
	"show_links" => false,
	'show_other' => false,
));
echo HTML::strip($result);
