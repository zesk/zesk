<?php

/* @var $link_widget Widget */
$link_widget = $this->link_widget;

/* @var $widget Widget */
$widget = $this->widget;

$link_widget_name = $this->link_widget_name;

$column = $this->column;
/* @var $object Model */
$object = $this->object;

// This should only be set on SUBMIT
$links = $object->get($link_widget_name);
if (empty($links)) {
	$links = $object->get($this->column);
}
$minimum_objects = $this->minimum_objects;
$maximum_objects = $this->maximum_objects;
if ($maximum_objects <= 0) {
	$maximum_objects = 100;
}
$template_name = "template-$column";
echo html::tag_open("div", array(
	"class" => "control-link-object",
	"data-minimum-objects" => $minimum_objects,
	"data-template" => $template_name,
	"data-maximum-objects" => $maximum_objects
));

echo html::tag('script', array(
	'id' => $template_name,
	"type" => "text/x-template"
), $blank_widget = $link_widget->render());

echo html::tag_open('div', '.links');
$n_objects = 0;
foreach ($links as $item) {
	$link_widget->object($item);
	echo $link_widget->render();
	$n_objects++;
	if ($n_objects >= $maximum_objects) {
		break;
	}
}
$n_extras = 0;
if ($n_objects < $minimum_objects) {
	$n_extras = $minimum_objects - $n_objects;
}
for($i = 0; $i < $n_extras; $i++) {
	echo $blank_widget;
}

echo html::tag_close('div');

if ($widget->option_bool("show_more", true)) {
	$this->response->jquery('$(".control-link-object button.more").on("click", function () {
		var $parent = $(this).parents(".control-link-object"),
		$tpl = $("#"+$parent.data("template")).html();
		$(".links", $parent).append($tpl);
		return false;
	});');
	$add_attrs = array(
		"class" => "form-control more"
	);
	echo html::tag('div', '.more', html::tag("button", $add_attrs, $widget->option('label_more', __("More ..."))));
}

echo html::tag_close('div');
