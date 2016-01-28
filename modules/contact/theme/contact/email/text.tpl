<?php
$object = $this->object;
/* @var $object Object */
$result = $object->output("view", array(
	"show_links" => false,
	'show_other' => false
));
echo html::strip($result);
