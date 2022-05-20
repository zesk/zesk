<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/* @var $this \zesk\Template */
/* @var $application \zesk\Application */
/* @var $locale \zesk\Locale */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
/* @var $current_user \zesk\User */
/* @var $widget \zesk\Widget */
echo $this->prefix;
echo $this->theme($this->theme_prefix, [], [
	'first' => true,
]);
if ($this->form_tag) {
	echo HTML::tag_open($this->form_tag, HTML::add_class($this->form_attributes, $this->class));
}
echo $this->theme($this->theme_header, [], [
	'first' => true,
]);
$odd = 0;
$invisible = '';
$theme_widgets = $this->theme_widgets;
$map = [];
$theme_variables = [];

foreach ($this->widgets as $widget) {
	$nolabel = $widget->optionBool('nolabel');
	$name = $widget->name();
	$prefix = "$name.";

	$map[$prefix . 'label'] = $label = $nolabel ? '' : HTML::tag('label', to_array($this->label_attributes) + [
		'for' => $widget->firstOption(to_list('id;column')),
	], $widget->label());
	$map[$prefix . 'widget_class'] = get_class($widget);
	$has_errors = $widget->has_errors();
	$errors = '';
	$map[$prefix . 'has-error'] = '';
	$widget_attributes = $this->widget_attributes;
	if ($has_errors) {
		$widget_attributes = HTML::add_class($widget_attributes, 'has-error');
		$map[$prefix . 'has_error'] = ' has-error'; // Class name is "has-error"
		if ($widget->optionBool('append_error', true)) {
			$errors .= HTML::tags('span', '.help-block error', $widget->errors());
			$widget->suffix($errors, true);
		}
	}
	$map[$prefix . 'errors'] = $errors;
	if ($widget->hasOption('help')) {
		$widget->suffix($map[$prefix . 'help'] = HTML::tag('div', '.help-block', $widget->option('help')), true);
	}
	if (!$widget->is_visible()) {
		$map[$name] = $map[$prefix . 'widget'] = $widget->render();
		if (!$theme_widgets) {
			$invisible .= $map[$name];
		}
		continue;
	}
	/* @var $widget Widget */
	if ($this->widget_wrap_tag) {
		$widget->wrap($this->widget_wrap_tag, $nolabel ? $this->nolabel_widget_wrap_attributes : $this->widget_wrap_attributes);
	}
	$widget_attributes = HTML::add_class($widget_attributes, $widget->context_class());
	if ($name) {
		$widget_attributes = HTML::add_class($widget_attributes, "form-widget-$name");
	}
	$theme_variables["widget_${name}"] = $widget;
	$map[$prefix . 'widget'] = $map[$prefix . 'render'] = $render = $widget->render();
	$map[$name] = $row = $this->widget_tag ? HTML::tag($this->widget_tag, HTML::add_class($widget_attributes, "odd-$odd"), $label . $render) : $label . $render;
	if (!$theme_widgets) {
		echo map($row, $map);
	}
	$odd = 1 - $odd;
}
if ($theme_widgets) {
	$theme_variables += ArrayTools::kprefix(ArrayTools::kreplace($map, '.', '_'), 'widget_');
	echo map($this->theme($theme_widgets, $theme_variables), $map);
}
// TODO: 2016-09-26 KMD Is this wrong? Should $theme_footer go after $theme_widgets, below?
// MOVED 2016-09-26 it's gotta be wrong, right?
echo $this->theme($this->theme_footer, [], [
	'first' => true,
]);
echo $invisible;
if ($request) {
	foreach (to_array($this->form_preserve_hidden) as $name) {
		echo HTML::input_hidden($name, $request->get($name));
	}
}
if ($this->form_tag) {
	echo HTML::tag_close($this->form_tag);
}
echo $this->theme($this->theme_suffix, [], [
	'first' => true,
]);
echo $this->suffix;
