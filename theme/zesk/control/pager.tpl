<?php

/* @var $this Template */

/* @var $response Response_HTML */
$response = $this->response;
/* @var $request Request */
$request = $this->request;

/* @var $object Model_List */
$object = $this->object;
if (!$response) {
	$response = Response::instance();
}
if (!$request) {
	$request = Request::instance();
}

/* @var $widget Control_Pager */
$widget = $this->widget;

$this->limit = $limit = $object->limit;
$this->total = $total = $object->total;
$this->offset = $this->current = $offset = $object->offset;

// Prevent child content to be output by render function


$widget->content_children = "";

if (!$this->always_show) {
	if ($total <= $limit)
		return;
}

$this->last_offset = $last_offset = $object->last_offset;

$response->cdn_css("/share/zesk/widgets/pager/pager.css", array(
	'root_dir' => ZESK_ROOT
));

$this->last_index = ($limit < 0) ? $total : min($offset + $limit, $total);
$this->url = url::query_format(url::current_uri(), array(
	"limit" => $limit
));

$showing = __($total === 0 ? 'Control_Pager:=Showing none' : 'Control_Pager:=Showing {0} - {1} of {2}', ($offset + 1), $this->last_index, $total);
$uri = url::current_uri();

echo html::tag_open('form', array(
	"method" => 'get',
	'action' => $uri
));
echo html::tag_open("div", array(
	"class" => 'control-pager'
));
$this->direction = -1;
echo html::tag("div", ".pager-arrow", $this->theme('control/pager/arrow', array(
	"image" => "pager-start",
	"offset" => 0,
	"name" => __("First page"),
	"disabled_name" => __("Already at first page")
)));
echo html::tag("div", ".pager-arrow", $this->theme('control/pager/arrow', array(
	"image" => "pager-prev",
	"offset" => max($offset - $limit, 0),
	"name" => __("Previous page"),
	"disabled_name" => __("Already at first page")
)));
$this->direction = 1;
echo html::tag("div", ".pager-arrow", $this->theme('control/pager/arrow', array(
	"image" => "pager-next",
	"offset" => $offset + $limit,
	"name" => __("Next page"),
	"disabled_name" => __("Already at last page")
)));
echo html::tag("div", ".pager-arrow", $this->theme('control/pager/arrow', array(
	"image" => "pager-end",
	"offset" => $last_offset,
	"name" => __("Last page"),
	"disabled_name" => __("Already at last page")
)));

if ($this->debug) {
	echo html::tag("div", array(
		"class" => "pager-debug"
	), "$offset/$limit/$total");
}

echo html::tag("div", array(
	"class" => "pager-state"
), $showing);

$attrs = $this->geta("preserve_hidden", array());
$attrs += $request->get();
$children = $this->children;
unset($children['limit']);
unset($attrs['limit']);
if (count($children) > 0) {
	foreach ($children as $child) {
		/* @var $child Widget */
		unset($attrs[$child->name()]);
		echo html::tag('div', array(
			"class" => $child->context_class()
		), $child->render());
	}
}

echo $this->theme("control/pager/limits");

echo html::tag_close('div');

foreach ($attrs as $k => $v) {
	echo html::hidden($k, $v);
}

echo html::tag_close('form');

