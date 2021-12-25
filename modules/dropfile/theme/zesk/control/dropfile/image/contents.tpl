<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/* @var $this \zesk\Template */
/* @var $locale \zesk\Locale */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
/* @var $current_user \zesk\User */
/* @var $object \zesk\Content_Image */
if (!$this->object) {
	echo $this->empty_string;
	$object_id = "";
} else {
	echo $this->theme('content/image', [
		"object" => $this->object,
		"width" => $this->width,
		"height" => $this->height,
	]);
	$object_id = $this->object->id;
}
echo HTML::input('hidden', $this->name, $object_id, [
	"id" => $this->name,
	"class" => 'dropfile-value',
]);

echo $this->theme('dropfile/overlay');
