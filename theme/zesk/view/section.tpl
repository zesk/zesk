<?php
$this->label_class = "col-sm-$this->column_count_label control-label";
$this->widget_attributes = html::add_class(to_array($this->widget_attributes), "form-group");

if ($this->section_title) {
	echo html::tag('h2', map($this->section_title, arr::kprefix($this->object->variables(), "object.")));
}

foreach ($this->children as $child) {
	/* @var $child Widget */
	$child->wrap('div', "col-sm-" . $this->get("column_count_widget",12));
}
echo $this->theme('control/widgets', array(
	'widgets' => $this->children
));