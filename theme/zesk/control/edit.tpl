<?php

/* @var $widget Widget */
echo $this->prefix;
echo $this->theme($this->theme_prefix, array(), array(
	"first" => true
));
if ($this->form_tag) {
	echo html::tag_open($this->form_tag, html::add_class($this->form_attributes, $this->class));
}
echo $this->theme($this->theme_header, array(), array(
	"first" => true
));
$odd = 0;
$invisible = "";
$theme_widgets = $this->theme_widgets;
$map = array();
foreach ($this->widgets as $widget) {
	$nolabel = $widget->option_bool("nolabel");
	$name = $widget->name();
	$prefix = "$name.";
	$map[$prefix . "label"] = $label = $nolabel ? "" : html::tag("label", to_array($this->label_attributes) + array(
		"for" => $widget->first_option("id;column")
	), $widget->label());
	$map[$prefix . "widget_class"] = get_class($widget);
	$has_errors = $widget->has_errors();
	$errors = "";
	$map[$prefix . 'has-error'] = "";
	$widget_attributes = $this->widget_attributes;
	if ($has_errors) {
		$widget_attributes = html::add_class($widget_attributes, 'has-error');
		$map[$prefix . 'has-error'] = " has-error";
		$errors .= html::tags('span', '.help-block error', $widget->errors());
		$widget->suffix($errors, true);
	}
	$map[$prefix . "errors"] = $errors;
	if ($widget->has_option('help')) {
		$widget->suffix($map[$prefix . 'help'] = html::tag('div', '.help-block', $widget->option('help')), true);
	}
	if (!$widget->is_visible()) {
		$map[$name] = $map[$prefix . "widget"] = $widget->render();
		if (!$theme_widgets) {
			$invisible .= $map[$name];
		}
		continue;
	}
	/* @var $widget Widget */
	if ($this->widget_wrap_tag) {
		$widget->wrap($this->widget_wrap_tag, $nolabel ? $this->nolabel_widget_wrap_attributes : $this->widget_wrap_attributes);
	}
	$widget_attributes = html::add_class($widget_attributes, $widget->context_class());
	if ($name) {
		$widget_attributes = html::add_class($widget_attributes, "form-widget-$name");
	}
	$map[$prefix . "widget"] = $render = $widget->render();
	$map[$name] = $row = $this->widget_tag ? html::tag($this->widget_tag, html::add_class($widget_attributes, "odd-$odd"), $label . $render) : $label . $render;
	if (!$theme_widgets) {
		echo map($row, $map);
	}
	$odd = 1 - $odd;
}
echo $this->theme($this->theme_footer, array(), array(
	"first" => true
));
if ($theme_widgets) {
	echo map($this->theme($theme_widgets), $map);
}
echo $invisible;
foreach (to_array($this->form_preserve_hidden) as $name) {
	echo html::input_hidden($name, $this->request->get($name));
}
if ($this->form_tag) {
	echo html::tag_close($this->form_tag);
}
echo $this->theme($this->theme_suffix, array(), array(
	"first" => true
));
echo $this->suffix;
