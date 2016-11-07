<?php
/**
 * 
 */
use zesk\HTML;

/* @var $this \zesk\Template */
/* @var $zesk \zesk\Kernel */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response_Text_HTML */
/* @var $current_user \User */
/* @var $object \Content_Image */

if (!$this->object) {
	echo $this->empty_string;
	$object_id = "";
} else {
	echo $this->theme('content/image', array(
		"object" => $this->object,
		"width" => $this->width,
		"height" => $this->height
	));
	$object_id = $this->object->id;
}
echo HTML::input('hidden', $this->name, $object_id, array(
	"id" => $this->name, "class" => 'dropfile-value'
));

echo $this->theme('dropfile/overlay');
