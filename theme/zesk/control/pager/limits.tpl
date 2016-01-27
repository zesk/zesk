<?php

/* @var $widget Control_Select */
$widget = $this->widget;

/* @var $object Model */
$object = $this->object;

/* @var $limit_widget Widget */
$limit_widget = $widget->child('limit');
if (!$limit_widget) {
	return;
}
if (!$limit_widget->is_visible()) {
	return;
}
echo html::tag("div", array(
	"class" => 'pager-limits form-group'
), html::tag("label", false, __('Control_Pager:=Show') . $limit_widget->render()));
