<?php
$widget = $this->widget;
/* @var $widget Widget */
$widget->content_children = "";
echo html::tag_open('div', '.buttonbar');
foreach ($widget->children as $widget) {
	/* @var $widget Widget */
	echo $widget->content;
}
echo html::tag_close('div');

