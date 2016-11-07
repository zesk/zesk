<?php
use zesk\HTML;

/* @var $this \zesk\Template */
/* @var $zesk \zesk\Kernel */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response_Text_HTML */
/* @var $current_user \User */

/* @var $link_widget Widget */
/* @var $object \zesk\Model */
/* @var $widget Widget */

$link_widget = $this->link_widget;

$widget = $this->widget;

$link_widget_name = $this->link_widget_name;

$column = $this->column;
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
echo HTML::tag_open("div", array(
	"class" => "control-link-object",
	"data-minimum-objects" => $minimum_objects,
	"data-template" => $template_name,
	"data-maximum-objects" => $maximum_objects
));

echo HTML::tag('script', array(
	'id' => $template_name,
	"type" => "text/x-template"
), $blank_widget = $link_widget->render());

echo HTML::tag_open('div', '.links');
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

echo HTML::tag_close('div');

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
	echo HTML::tag('div', '.more', HTML::tag("button", $add_attrs, $widget->option('label_more', __("More ..."))));
}

echo HTML::tag_close('div');
