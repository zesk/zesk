<?php

$object = $this->object;
$widget = $this->widget;

$col = $widget->column();
$name = $object->apply_map($widget->name());

$options = $this->control_options;
if (!is_array($options) || count($options) === 0) {
	return $widget->empty_string;
}

$attributes = $this->input_attributes + $this->data_attributes;

$no_value = $widget->option("novalue", "");
$no_name = $widget->option("noname", $widget->required() ? "" : __('Control_Select:=-- Select --'));
/* @var $widget Widget */
$has_refresh_message = $widget->has_option("refresh_message");
if ($widget->has_option("onchange")) {
	$attributes["onchange"] = $widget->option("onchange");
}
if ($widget->option("refresh", false)) {
	$attributes["onchange"] = ($has_refresh_message ? "this.form.elements['" . $name . "_sv'].value=1; " : "") . "this.form.submit()";
	if ($has_refresh_message) {
		echo html::hidden($name . "_sv", "");
	}
}
$sValue = $widget->value();
$array_index = $widget->option("array_index");
if ($array_index !== false && is_array($sValue)) {
	$sValue = avalue($sValue, $array_index);
}
$optgroup = to_bool($this->optgroup);
unset($attributes['name']);
if ($widget->option("hide_single", $widget->required()) && (count($options) === 1 && $optgroup === false)) {
	if ($widget->option('hide_single_text')) {
		$single_tag_contents = "";
	} else {
		reset($options);
		$single_tag_contents = current($options);
		$single_tag = $widget->option("single_tag", "");
		if ($single_tag) {
			$single_tag_contents = html::tag($single_tag, $widget->option_array("single_tag_attributes", array()), $single_tag_contents);
		}
	}
	echo html::hidden($name, key($options)) . $single_tag_contents;
	return;
}
$escape_values = $this->escape_values;
$escape_option_group_values = $widget->option_bool("escape_option_group_values", true);
$attributes = $object->apply_map($attributes);
$attributes['class'] = css::add_class(avalue($attributes, 'class'), 'form-control');
$attributes['name'] = $name;
$attributes['id'] = $this->id;

echo html::tag_open('select', $attributes);
if (!empty($no_name)) {
	echo html::tag('option', array(
		'value' => $no_value
	), $escape_values ? htmlspecialchars($no_name) : $no_name);
}
$max_option_length = $widget->option_integer('max_option_length', 100);
foreach ($options as $k => $v) {
	if (is_array($v)) {
		if ($optgroup) {
			echo html::tag_open("optgroup", array(
				"label" => $escape_option_group_values ? htmlspecialchars($k) : $k
			));
			foreach ($v as $og_k => $og_v) {
				if ($max_option_length > 0) {
					$og_v = html::ellipsis($og_v, $max_option_length, $widget->option('dot_dot_dot', '...'));
				}
				echo html::tag('option', array(
					'value' => $og_k,
					'selected' => (strval($sValue) === strval($og_k))
				), ($escape_values ? htmlspecialchars($og_v) : $og_v));
			}
			echo html::tag_close("optgroup");
		} else {
			echo $this->theme('control/select/option', array(
				"value" => $k,
				"escape_values" => $escape_values,
				"selected" => (strval($sValue) === strval($k))
			) + $v);
		}
	} else {
		if ($max_option_length > 0) {
			$v = html::ellipsis($v, $max_option_length, $widget->option('dot_dot_dot', '...'));
		}
		$debug = "";
		//		$debug = strval($sValue) . "===" . strval($k);
		echo html::tag('option', array(
			'value' => $k,
			'selected' => (strval($sValue) === strval($k))
		), ($escape_values ? htmlspecialchars($v) : $v) . $debug);
	}
}
echo html::tag_close('select');
