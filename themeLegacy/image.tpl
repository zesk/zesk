<?php declare(strict_types=1);
namespace zesk;

/* @var $this Template */
/* @var $locale Locale */
/* @var $application Application */
/* @var $router Router */
/* @var $route Route */
/* @var $request Request */
/* @var $response Response */
$src = $this->src;
$width = $this->width;
$height = $this->height;
$name = 'image-' . md5(microtime());
$attributes = $this->getArray('attributes') + [
	'class' => $this->class,
	'query' => $this->query,
	'name' => $name,
] + $this->variables() + [
	'id' => $this->id,
];
$widget = View_Image::scaled_widget($application, $width, $height, null, $attributes);
$widget->setResponse($response);

$model = $application->modelFactory('zesk\\Model');
$model->src = $src;

try {
	echo $widget->execute($model);
} catch (Exception_Semantics $e) {
	echo '<!--' . $e->getMessage() . '-->';
}

$this->inherit($widget->options(['scale_src', 'scale_path']));
