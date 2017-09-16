<?php
namespace zesk;

/* @var $this \zesk\Template */
/* @var $zesk \zesk\Kernel */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response_Text_HTML */
/* @var $current_user \zesk\User */
$src = $this->src;
$width = $this->width;
$height = $this->height;
$name = 'image-' . md5(microtime());
$attributes = $this->geta('attributes', array()) + array(
	'class' => $this->class,
	'query' => $this->query,
	'name' => $name
) + $this->variables + array(
	'id' => $this->id
);
$widget = View_Image::scaled_widget($application, $width, $height, null, $attributes);

$model = $application->model_factory("zesk\\Model");
$model->src = $src;

echo $widget->execute($model);

$this->inherit($widget->options_include('scale_src;scale_path'));
