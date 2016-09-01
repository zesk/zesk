<?php
/* @var $this Template */
if (false) {
	/* @var $this Template */
	
	$zesk = $this->zesk;
	/* @var $zesk zesk\Kernel */
	
	$application = $this->application;
	/* @var $application TimeBank */
	
	/* @var $request Request */
	$request = $this->request;
}
if (!$request instanceof Request) {
	$request = $application->request();
}
/* @var $object Object */
$object = $this->object;
/* @var $widget Widget */
$widget = $this->widget;

$widget->child_content = "";

$table_attributes = $this->table_attributes;
if (!is_array($table_attributes)) {
	$table_attributes = array();
}

$invisibles = "";
$form_attributes = $this->geta('form_attributes');
$form_attributes['method'] = avalue($form_attributes, 'method', 'post');
if ($this->ajax) {
	$invisibles .= html::hidden("ajax", 1);
}
$form_attributes['class'] = css::add_class(avalue($form_attributes, 'class'), 'form-' . $this->get('form-style', 'horizontal'));

$form_attributes['role'] = "form";
echo html::tag_open("form", $form_attributes);

echo html::tag_open('div', $table_attributes);

foreach ($widget->children() as $name => $child) {
	/* @var $child Widget */
	if ($name === "widgets") {
		continue;
	}
	$label = "";
	if ($child->label) {
		$label = html::tag('label', array(
			'for' => $child->id(),
			'class' => $this->inline ? 'sr-only' : ''
		), $child->label);
	}
	echo html::div('.form-group', $label . $child->content . html::etag('p', '.help-block', $this->help));
}

$widgets = $widget->child("widgets");
if ($widgets) {
	foreach ($widgets->children() as $child) {
		/* @var $w Widget */
		$this->child = $child;
		if ($child->is_visible($object)) {
			echo $this->theme(arr::suffix($zesk->classes->hierarchy($this->widget), "/child"));
		} else {
			$invisibles .= $child->content;
		}
	}
}
echo html::tag_close("div");

echo html::hidden("ref", $request->get("ref"));
if (method_exists($object, 'id_column')) {
	echo html::hidden($object->id_column(), $object->id()) . $invisibles;
}

$buttonbar = $widget->child('buttonbar');
if ($buttonbar) {
	echo $buttonbar->content;
}

echo html::tag_close("form");

$widget->content_children = "";