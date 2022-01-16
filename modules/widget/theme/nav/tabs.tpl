<?php declare(strict_types=1);
/**
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
namespace zesk;

/* @var $this \zesk\Template */
/* @var $application \zesk\Application */
/* @var $locale \zesk\Locale */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
/* @var $current_user \zesk\User */
/* @var $widget Widget */
$upload = $widget ? $widget->upload() : false;

$form = [
	'class' => CSS::add_class('form-horizontal', $this->class),
	'action' => $this->request->path(),
	'method' => 'post',
	'enctype' => $upload ? "multipart/form-data" : "",
];

echo HTML::tag_open('form', $form);

$prefix = [];
$navs = [];
$suffix = [];
foreach ($this->children as $widget) {
	if ($widget->optionBool('nav')) {
		$navs[] = $widget;
	} elseif (count($navs) === 0) {
		echo $widget->render();
	} else {
		$suffix[] = $widget;
	}
}

/*
 * Standard tab functionality messes with # and causes the page to jump
 * This uses the hash in the URL, and prevents the page jump by temporarily
 * removing the id
 */
$response->jquery('$("[data-toggle=tab]").off("click.tabs").on("click.tabs", function (e) {
	var
	$this = $(this),
	$form = $this.parents("form"),
	href = $this.attr("href"),
	$target = $(href),
	id = href.substr(1);
	if ($form.length) {
		$form.attr("action", $form.attr("action").left("#") + href);
	}
	e.preventDefault();
	e.stopPropagation();
	$this.tab("show");
	$target.attr("id", "");
	document.location = href;
	$target.attr("id", id);
	return false;
});');

$response->html()->jquery('$(".nav-tabs li:first a,li a[href=\"#" + document.URL.right("#") + "\"]").tab("show");');
$title = $response->html()->title();
?>
<div class="nav-tabs">
	<ul class="nav nav-tabs">
	<?php
	$content = "";
	$selected_tab = $first_name = null;
	foreach ($this->children as $widget) {
		$name = $widget->column();
		if ($first_name === null) {
			$first_name = $name;
		}
		if ($name === $this->selected_tab) {
			$selected_tab = $name;
			break;
		}
	}
	if ($selected_tab === null) {
		$selected_tab = $first_name;
	}
	/* @var $widget Widget */
	foreach ($navs as $widget) {
		$widget_content = $widget->render();
		if (empty($widget_content)) {
			continue;
		}
		$errors = $widget->children_errors();
		$name = $widget->column();
		$item_class = $name === $selected_tab ? 'active' : '';
		$item_class = CSS::add_class($item_class, $widget->context_class());
		echo HTML::tag('li', [
			'id' => 'nav-link-' . $widget->id(),
			'class' => CSS::add_class($item_class, count($errors) > 0 ? 'error' : ''),
		], HTML::tag('a', [
			'href' => '#' . $name,
			'data-toggle' => "tab",
		], $widget->label() . HTML::etag('span', '.badge error', count($errors))));
		$content .= HTML::tag('div', [
			'id' => $name,
			'class' => 'tab-pane' . ($name === $selected_tab ? ' active' : ''),
		], $widget_content);
		$widget->content = "";
		$widget->content_children = "";
	}
	?>
	</ul>
	<div class="tab-content"><?php

	echo $content;
	?></div>
</div>
<?php
/* @var $widget Widget */
foreach ($suffix as $widget) {
	echo $widget->render();
}

echo HTML::tag_close('form');
