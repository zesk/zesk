<?php declare(strict_types=1);
/**
 *
 */
use zesk\HTML;
use zesk\ArrayTools;

/**
 * Note similarities here and zesk/control/edit.tpl
 * TODO Consider merging the two to have consistency?
 *
 */
$results = [];
$hidden = '';
foreach ($this->children as $child) {
	$child = clone $child;
	/* @var $child Widget */
	$name = $child->column();
	if ($child->hasOption('help')) {
		$suffix = HTML::tag('div', '.help-block', $child->option('help'));
		$child->suffix($suffix, true);
		$child->setOption('help', null);
	}
	if ($child->has_errors()) {
		$suffix = HTML::tags('div', '.help-block error', $child->errors());
		$child->suffix($suffix, true);
	}

	$child->object($this->object);
	$content = $child->render();
	$label = $results[$name . '.label'] = HTML::tag('label', [
		'class' => $this->get('label_class', 'label label-default'),
		'for' => $child->id(),
	], $child->label());
	if ($child->optionBool('nolabel', $this->nolabel)) {
		$label = '';
	}
	$widget_tag = $child->option('widget_tag', $this->get('widget_tag', 'div'));
	$widget_attributes = HTML::addClass(to_array($this->widget_attributes), $child->contextClass());

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
	echo map($this->theme($this->theme_widgets), ArrayTools::flatten(ArrayTools::prefixKeys($results, 'widget.') + ArrayTools::prefixKeys($this->variables, 'template.') + ArrayTools::prefixKeys($this->object->variables(), 'object.')));
}
echo $hidden;
