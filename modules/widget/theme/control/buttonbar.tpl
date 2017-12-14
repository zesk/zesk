<?php
namespace zesk;

$widget = $this->widget;
/* @var $widget Widget */
$widget->content_children = "";
echo HTML::tag_open('div', '.buttonbar');
foreach ($widget->children as $widget) {
	/* @var $widget Widget */
	echo $widget->content;
}
echo HTML::tag_close('div');

