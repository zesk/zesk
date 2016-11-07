<?php
/**
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
namespace zesk;

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
		echo HTML::hidden($name . "_sv", "");
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
			$single_tag_contents = HTML::tag($single_tag, $widget->option_array("single_tag_attributes", array()), $single_tag_contents);
		}
	}
	echo HTML::hidden($name, key($options)) . $single_tag_contents;
	return;
}
$escape_values = $this->escape_values;
$escape_option_group_values = $widget->option_bool("escape_option_group_values", true);
$attributes = $object->apply_map($attributes);
$attributes['class'] = CSS::add_class(avalue($attributes, 'class'), 'form-control');
$attributes['name'] = $name;
$attributes['id'] = $this->id;

echo HTML::tag_open('select', $attributes);
if (!empty($no_name)) {
	echo HTML::tag('option', array(
		'value' => $no_value
	), $escape_values ? htmlspecialchars($no_name) : $no_name);
}
$max_option_length = $widget->option_integer('max_option_length', 100);
foreach ($options as $k => $v) {
	if (is_array($v)) {
		if ($optgroup) {
			echo HTML::tag_open("optgroup", array(
				"label" => $escape_option_group_values ? htmlspecialchars($k) : $k
			));
			foreach ($v as $og_k => $og_v) {
				if (is_array($og_v)) {
					$content = $og_v['content'];
					unset($og_v['content']);
					$attributes = $og_v;
				} else {
					$attributes = array();
					$content = $og_v;
				}
				if ($max_option_length > 0) {
					$content = HTML::ellipsis($content, $max_option_length, $widget->option('dot_dot_dot', '...'));
				}
				echo HTML::tag('option', array(
					'value' => $og_k,
					'selected' => (strval($sValue) === strval($og_k))
				) + $attributes, ($escape_values ? htmlspecialchars($content) : $content));
			}
			echo HTML::tag_close("optgroup");
		} else {
			echo $this->theme('control/select/option', array(
				"value" => $k,
				"escape_values" => $escape_values,
				"selected" => (strval($sValue) === strval($k))
			) + $v);
		}
	} else {
		if ($max_option_length > 0) {
			$v = HTML::ellipsis($v, $max_option_length, $widget->option('dot_dot_dot', '...'));
		}
		$debug = "";
		//		$debug = strval($sValue) . "===" . strval($k);
		echo HTML::tag('option', array(
			'value' => $k,
			'selected' => (strval($sValue) === strval($k))
		), ($escape_values ? htmlspecialchars($v) : $v) . $debug);
	}
}
echo HTML::tag_close('select');
