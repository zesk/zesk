<?php

namespace zesk;

if (false) {
	$value = $this->value;
	$selected = $this->selected;
	$variables = $this->variables;
	$label = $this->label;
	$escape_values = $this->escape_values;
}
$data_attrs = HTML::data_attributes($variables);
unset($data_attrs['data-attributes']);
$label = $this->label;
echo HTML::tag('option', array(
	'value' => $value,
	'selected' => $selected
) + $data_attrs, $escape_values ? htmlspecialchars($label) : $label);
