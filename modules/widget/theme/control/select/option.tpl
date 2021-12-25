<?php declare(strict_types=1);
namespace zesk;

$variables = $this->variables;
$value = $this->value;
$selected = $this->selected;
$escape_values = $this->escape_values;
$label = $this->label;

$data_attrs = HTML::data_attributes($variables);
unset($data_attrs['data-attributes']);
echo HTML::tag('option', [
	'value' => $value,
	'selected' => $selected,
] + $data_attrs, $escape_values ? htmlspecialchars($label) : $label);
