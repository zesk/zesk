<?php
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

/* @var $url string */
/* @var $offset integer */
/* @var $current integer */
/* @var $total integer */
/* @var $icon string */
/* @var $title string */
/* @var $disabled_title string */
/* @var $direction integer */
/* @var $last_index integer */
$disabled = true;
if ($direction < 0) {
	if ($current > 0) {
		$disabled = false;
	} else {
		$title = $disabled_title;
	}
} else {
	if ($last_index < $total) {
		$disabled = false;
	} else {
		$title = $disabled_title;
	}
}

if ($disabled) {
	$attrs = array(
		'class' => 'disabled',
	);
} else {
	$href = URL::query_format($url, array(
		"offset" => $offset,
	));
	if ($this->has("ajax_id")) {
		$ajax_id = $this->ajax_id;
		$href = "$.get('" . $href . "',function(data){\$('#$ajax_id').html(data);})";
		$attrs = array(
			"href" => "javascript:noop()",
			"onclick" => $href,
		);
	} else {
		$attrs = array(
			"href" => $href,
		);
	}
}
$attrs['title'] = $title;
$attrs = HTML::add_class($attrs, "btn btn-sm btn-pager");
echo HTML::tag("a", $attrs, HTML::span(array(
	"class" => "glyphicon glyphicon-$icon",
	"disabled" => $disabled,
), null));
