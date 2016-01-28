<?php

if (!$this->object) {
	echo $this->empty_string;
	$object_id = "";
} else {
	echo theme('content/image', array(
		"object" => $this->object,
		"width" => $this->width,
		"height" => $this->height
	));
	$object_id = $this->object->id;
}
echo html::input('hidden', $this->name, $object_id, array(
	"id" => $this->name, "class" => 'dropfile-value'
));

echo theme('dropfile/overlay');