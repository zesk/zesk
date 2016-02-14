<?php
/**
 * Note similarities here and control/edit.tpl
 *
 *
 */
$variables = $this->variables;
$results = array();
$hidden = "";
// echo "<h3>BEGIN widgets.tpl: ".get_class($widget)." : " . $name . " " . count($children) . "</h3>";
// echo html::tag('code', _backtrace(10));
foreach ($this->children as $child) {
	$child = clone $child;
	/* @var $child Widget */
	$name = $child->column();
	if ($child->has_option('help')) {
		$suffix = html::tag('div', '.help-block', $child->option('help'));
		$child->suffix($suffix, true);
		$child->set_option('help', null);
	}
	if ($child->has_errors()) {
		$suffix = html::tags('div', '.help-block error', $child->errors());
		$child->suffix($suffix, true);
	}

	$child->object($this->object);
	$content = $child->render();
	$label = $results[$name . '.label'] = html::tag('label', array(
		'class' => $this->get('label_class', 'label label-default'),
		'for' => $child->id()
	), $child->label());
	if ($child->option_bool('nolabel', $this->nolabel)) {
		$label = "";
	}
	$cell = $results[$name . '.cell'] = empty($content) ? $content : html::tag($child->option('widget_tag', $this->get('widget_tag', 'div')), html::add_class(to_array($this->widget_attributes), $child->context_class()), $label . $content);
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
//echo "<h3>END widgets.tpl: ".get_class($widget)."</h3>";
