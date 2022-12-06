<?php declare(strict_types=1);

/**
 * @copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

/* @var $object Model */
/* @var $widget Control_Select */
/* @var $column string */
/* @var $name string */
/* @var $value string|array */
/* @var $default string */
/* @var $multiple boolean */
/* @var $multiple boolean */
$col = $column;

$options = $this->control_options;
if (!is_array($options) || count($options) === 0) {
	return $widget->empty_string;
}

$attributes = $this->input_attributes + $this->data_attributes;

$no_value = $widget->option('novalue', '');
$no_name = $widget->option('noname', $widget->required() ? '' : __('Control_Select:=-- Select --'));
/* @var $widget Widget */
$has_refresh_message = $widget->hasOption('refresh_message');
if ($widget->hasOption('onchange')) {
	$attributes['onchange'] = $widget->option('onchange');
}
if ($widget->option('refresh', false)) {
	$attributes['onchange'] = ($has_refresh_message ? 'this.form.elements[\'' . $name . '_sv\'].value=1; ' : '') . 'this.form.submit()';
	if ($has_refresh_message) {
		echo HTML::hidden($name . '_sv', '');
	}
}
$array_index = $widget->option('array_index');
if ($array_index !== false && is_array($value)) {
	$value = avalue($value, $array_index);
}
$optgroup = toBool($this->optgroup);
unset($attributes['name']);
if ($widget->is_single()) {
	reset($options);
	if ($widget->hasOption('hide_single_text')) {
		$single_tag_contents = strval($widget->option('hide_single_text'));
	} else {
		$single_tag_contents = current($options);
		$single_tag = $widget->option('single_tag', '');
		if ($single_tag) {
			$single_tag_contents = HTML::tag($single_tag, $widget->optionArray('single_tag_attributes', []), $single_tag_contents);
		}
	}
	if (!$value) {
		$value = key($options);
	}
	echo HTML::hidden($name, $value);
	if (is_array($single_tag_contents)) {
		$single_tag_contents = $single_tag_contents['content'];
	}
	echo $single_tag_contents;
	return;
}
$escape_values = $this->escape_values;
$escape_option_group_values = $widget->optionBool('escape_option_group_values', true);
$attributes = $object->applyMap($attributes);
$attributes['class'] = CSS::addClass(avalue($attributes, 'class'), 'form-control');
$attributes['name'] = $name . ($multiple ? '[]' : '');
$attributes['id'] = $this->id;
$attributes['multiple'] = $multiple;

echo HTML::tag_open('select', $attributes);
if (!empty($no_name)) {
	echo HTML::tag('option', [
		'value' => $no_value,
	], $escape_values ? htmlspecialchars($no_name) : $no_name);
}
$max_option_length = $widget->optionInt('max_option_length', 100);
$values = $multiple ? ArrayTools::flatten(to_list($value)) : [
	$value,
];
foreach ($options as $k => $v) {
	if (is_array($v)) {
		if ($optgroup) {
			echo HTML::tag_open('optgroup', [
				'label' => $escape_option_group_values ? htmlspecialchars($k) : $k,
			]);
			foreach ($v as $og_k => $og_v) {
				if (is_array($og_v)) {
					$content = $og_v['content'];
					unset($og_v['content']);
					$attributes = $og_v;
				} else {
					$attributes = [];
					$content = $og_v;
				}
				if ($max_option_length > 0) {
					$content = HTML::ellipsis($content, $max_option_length, $widget->option('dot_dot_dot', '...'));
				}
				echo HTML::tag('option', [
					'value' => $og_k,
					'selected' => in_array(strval($og_k), $values),
				] + $attributes, ($escape_values ? htmlspecialchars($content) : $content));
			}
			echo HTML::tag_close('optgroup');
		} elseif (isset($v['content'])) {
			$content = $v['content'];
			echo HTML::tag('option', [
				'value' => $k,
				'selected' => in_array(strval($k), $values),
			] + $v, ($escape_values ? htmlspecialchars($content) : $content));
		} else {
			echo $this->theme('zesk/control/select/option', [
				'value' => $k,
				'escape_values' => $escape_values,
				'selected' => in_array(strval($k), $values),
			] + $v);
		}
	} else {
		if ($max_option_length > 0) {
			$v = HTML::ellipsis($v, $max_option_length, $widget->option('dot_dot_dot', '...'));
		}
		$debug = '';
		echo HTML::tag('option', [
			'value' => $k,
			'selected' => in_array(strval($k), $values),
		], ($escape_values ? htmlspecialchars($v) : $v) . $debug);
	}
}
echo HTML::tag_close('select');
// echo "Default: $default<br />";
// echo "Object: <pre>"._dump($object->variables())."</pre><br />";
