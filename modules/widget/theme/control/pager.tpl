<?php
/**
 * 
 */
namespace zesk;

/* @var $this \zesk\Template */
/* @var $locale \zesk\Locale */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
/* @var $current_user \User */
/* @var $object Model_List */
if (!$request) {
	$request = $application->request();
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

$this->last_index = ($limit < 0) ? $total : min($offset + $limit, $total);
$this->url = URL::query_format($request->uri(), array(
	"limit" => $limit
));

$showing = __($total === 0 ? 'Control_Pager:=Showing none' : 'Control_Pager:=Showing {0} - {1} of {2}', ($offset + 1), $this->last_index, $total);
$uri = $request->uri();

echo HTML::tag_open('form', array(
	"method" => 'get',
	'action' => $uri
));
echo HTML::tag_open("div", array(
	"class" => 'control-pager btn-toolbar'
));
{
	echo HTML::tag_open("div", array(
		"class" => ' btn-group pagination'
	));
	{
		$this->direction = -1;
		
		echo $this->theme('zesk/control/pager/arrow', array(
			"icon" => "fast-backward",
			"offset" => 0,
			"title" => __("First page"),
			"disabled_title" => __("Already at first page")
		));
		echo $this->theme('zesk/control/pager/arrow', array(
			"icon" => "step-backward",
			"offset" => max($offset - $limit, 0),
			"title" => __("Previous page"),
			"disabled_title" => __("Already at first page")
		));
		$this->direction = 1;
		echo $this->theme('zesk/control/pager/arrow', array(
			"icon" => "step-forward",
			"offset" => $offset + $limit,
			"title" => __("Next page"),
			"disabled_title" => __("Already at last page")
		));
		echo $this->theme('zesk/control/pager/arrow', array(
			"icon" => "fast-forward",
			"offset" => $last_offset,
			"title" => __("Last page"),
			"disabled_title" => __("Already at last page")
		));
	}
	echo HTML::tag_close('div');
	
	if ($this->debug) {
		echo HTML::tag("div", array(
			"class" => "pager-debug"
		), "$offset/$limit/$total");
	}
	
	echo HTML::tag("div", array(
		"class" => "pager-state btn-group"
	), HTML::tag('a', 'btn disabled pager-text btn-pager-stage btn-sm', $showing));
	
	$attrs = $this->geta("preserve_hidden", array());
	$attrs += $request->get();
	$children = $this->children;
	unset($children['limit']);
	unset($attrs['limit']);
	if (count($children) > 0) {
		foreach ($children as $child) {
			/* @var $child Widget */
			unset($attrs[$child->name()]);
			echo HTML::tag('div', array(
				"class" => $child->context_class()
			), $child->render());
		}
	}
	
	echo $this->theme("zesk/control/pager/limits");
}
echo HTML::tag_close('div');

foreach ($attrs as $k => $v) {
	echo HTML::hidden($k, $v);
}

echo HTML::tag_close('form');

