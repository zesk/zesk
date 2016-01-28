<?php
$object = $this->object;
/* @var $object Object */

echo $object->output("view", array(
	"show_links" => false,
	'show_other' => false
));
