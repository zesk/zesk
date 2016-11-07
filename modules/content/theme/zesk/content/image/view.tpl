<?php
/**
 * 
 */
namespace zesk;

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
$router = $this->router;

$object = $this->object;

$width = $this->get('width', $object->width);
$height = $this->get('height', $object->height);

$src = $this->get('src');
$original_src = $src;
if (!$src) {
	$src = $router->get_route("image", $object, array(
		'width' => $width,
		'height' => $height
	));
	$options = array();
	if ($object->width > 1000 || $object->height > 1000) {
		list($width, $height) = $object->constrain_dimensions(1000, 1000);
		$options = compact("width", "height");
	}
	$original_src = $router->get_route("image", $object, $options);
}
$title = $this->get('title', $object->title);
$attr = array(
	'alt' => $title,
	'title' => $title,
	'data-class' => get_class($object),
	'data-id' => $object->id,
	'data-src' => $original_src,
	'src' => $src
);

echo HTML::div_open('.content-image');
echo HTML::tag('img', $attr, null);
echo $object->theme('image-caption');
echo HTML::div_close();
