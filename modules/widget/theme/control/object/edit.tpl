<?php
/**
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
namespace zesk;

/* @var $this \zesk\Template */
/* @var $locale \zesk\Locale */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response_Text_HTML */
/* @var $current_user \zesk\User */
/* @var $object \zesk\ORM */
/* @var $widget Widget */
if (!$request instanceof Request) {
	$request = $application->request();
}

$widget->child_content = "";

$table_attributes = $this->table_attributes;
if (!is_array($table_attributes)) {
	$table_attributes = array();
}

$invisibles = "";
$form_attributes = $this->geta('form_attributes');
$form_attributes['method'] = avalue($form_attributes, 'method', 'post');
if ($this->ajax) {
	$invisibles .= HTML::hidden("ajax", 1);
}
$form_attributes['class'] = CSS::add_class(avalue($form_attributes, 'class'), 'form-' . $this->get('form-style', 'horizontal'));

$form_attributes['role'] = "form";
echo HTML::tag_open("form", $form_attributes);

echo HTML::tag_open('div', $table_attributes);

foreach ($widget->children() as $name => $child) {
	/* @var $child Widget */
	if ($name === "widgets") {
		continue;
	}
	$label = "";
	if ($child->label) {
		$label = HTML::tag('label', array(
			'for' => $child->id(),
			'class' => $this->inline ? 'sr-only' : ''
		), $child->label);
	}
	echo HTML::div('.form-group', $label . $child->content . HTML::etag('p', '.help-block', $this->help));
}

$widgets = $widget->child("widgets");
if ($widgets) {
	foreach ($widgets->children() as $child) {
		/* @var $w Widget */
		$this->child = $child;
		if ($child->is_visible($object)) {
			echo $this->theme(ArrayTools::suffix($zesk->classes->hierarchy($this->widget), "/child"));
		} else {
			$invisibles .= $child->content;
		}
	}
}
echo HTML::tag_close("div");

echo HTML::hidden("ref", $request->get("ref"));
if (method_exists($object, 'id_column')) {
	echo HTML::hidden($object->id_column(), $object->id()) . $invisibles;
}

$buttonbar = $widget->child('buttonbar');
if ($buttonbar) {
	echo $buttonbar->content;
}

echo HTML::tag_close("form");

$widget->content_children = "";
