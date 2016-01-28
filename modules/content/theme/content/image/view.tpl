<?php

/* @var $router Router */
$router = $this->router;

/* @var $object Content_Image */
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

echo html::div_open('.content-image');
echo html::tag('img', $attr, null);
echo $object->render('image-caption');
echo html::div_close();