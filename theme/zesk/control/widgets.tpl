<?php
/**
 * 
 */
use zesk\HTML;
use zesk\arr;

/**
 * Note similarities here and control/edit.tpl
 * TODO Consider merging the two to have consistency?
 *
 */
$variables = $this->variables;
$results = array();
$hidden = "";
foreach ($this->children as $child) {
	$child = clone $child;
	/* @var $child Widget */
	$name = $child->column();
	if ($child->has_option('help')) {
		$suffix = HTML::tag('div', '.help-block', $child->option('help'));
		$child->suffix($suffix, true);
		$child->set_option('help', null);
	}
	if ($child->has_errors()) {
		$suffix = HTML::tags('div', '.help-block error', $child->errors());
		$child->suffix($suffix, true);
	}
	
	$child->object($this->object);
	$content = $child->render();
	$label = $results[$name . '.label'] = HTML::tag('label', array(
		'class' => $this->get('label_class', 'label label-default'),
		'for' => $child->id()
	), $child->label());
	if ($child->option_bool('nolabel', $this->nolabel)) {
		$label = "";
	}
	$widget_tag = $child->option('widget_tag', $this->get('widget_tag', 'div'));
	$widget_attributes = HTML::add_class(to_array($this->widget_attributes), $child->context_class());
	
	$cell = $results[$name . '.cell'] = empty($content) ? $content : HTML::tag($widget_tag, $widget_attributes, $label . $content);
	$results[$name] = $content;
	
	if ($child->is_visible()) {
		if (!$this->theme_widgets) {
			if ($this->debug) {
				echo "<!-- $name:cell { -->";
			}
			echo map($cell, $results);
			if ($this->debug) {
				echo "<!-- } $name:cell -->";
			}
		}
	} else {
		$hidden .= $content;
	}
}
if ($this->theme_widgets) {
	echo map($this->theme($this->theme_widgets), arr::flatten(arr::kprefix($results, 'widget.') + arr::kprefix($this->variables, 'template.') + arr::kprefix($this->object->variables(), 'object.')));
}
echo $hidden;
