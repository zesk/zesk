<?php

if (false) {
	$value = $this->value;
	$selected = $this->selected;
	$variables = $this->variables;
	$label = $this->label;
	$escape_values = $this->escape_values;
}
$data_attrs = html::data_attributes($variables);
unset($data_attrs['data-attributes']);
$label = $this->label;
echo html::tag('option', array(
	'value' => $value,
	'selected' => $selected
) + $data_attrs, $escape_values ? htmlspecialchars($label) : $label);
