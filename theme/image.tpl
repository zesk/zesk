<?php
namespace zesk;

/* @var $this Template */
$src = $this->src;
$width = $this->width;
$height = $this->height;
$name = 'image-' . md5(microtime());
$attributes = $this->geta('attributes', array()) + array(
	'class' => $this->class,
	'query' => $this->query,
	'name' => $name,
) + $this->variables + array(
	'id' => $this->id
);
$widget = View_Image::scaled_widget($width, $height, null, $attributes);

$model = new Model();
$model->src = $src;

echo $widget->execute($model);

$this->inherit($widget->options_include('scale_src;scale_path'));
