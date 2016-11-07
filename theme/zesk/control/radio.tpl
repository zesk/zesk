<?php

/* @var $widget Control_Radio */
$widget = $this->widget;
/* @var $object Model */
$object = $this->object;

$col = $widget->column();
$name = $widget->name();
$opts = $this->control_options;
$base_attrs = $widget->options_include($widget->input_attribute_names());
$id_base = avalue($base_attrs, 'id', $name);
$base_attrs['name'] = $name;
$base_attrs['type'] = 'radio';
$result = "";
$sel_k = strval($object->get($col, $widget->option("default", '')));
$refresh = $widget->option_bool("refresh");
$suffix = "";
if ($refresh) {
	$suffix = HTML::hidden($name . "_cont", "");
}
$debug = $widget->option_bool('debug');
$content = "";
foreach ($opts as $k => $v) {
	$attrs = $base_attrs;
	if (!is_array($v)) {
		$v = array(
			"label" => $v
		);
	}
	$label = avalue($v, "label", "");
	unset($v['label']);
	$attrs += $v;
	$attrs['value'] = $k;
	$attrs['id'] = $id_base . $k;
	if ($refresh && !array_key_exists("onclick", $attrs)) {
		$attrs['onclick'] = "this.form.elements['${name}_cont'] = 1; this.form.submit()";
	}
	if (strval($k) === $sel_k) {
		$attrs['checked'] = 'checked';
	} else {
		unset($attrs['checked']);
	}
	$content .= HTML::tag("div", ".radio", HTML::tag("label", HTML::tag("input", $attrs, null) . $label . ($debug ? " " . _dump($k) : '')));
}
$result = HTML::tag("div", array(
	"class" => "control-radio"
), $content);
echo $result . $suffix . ($debug ? " Selected K: " . _dump($sel_k) . ";" : "");
