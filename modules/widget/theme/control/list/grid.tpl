<?php
/**
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
namespace zesk;

foreach ($this->widgets as $widget) {
	/* @var $widget Widget */
	if (!$widget->is_visible()) {
		continue;
	}
	$save_render = $widget->save_render();
	if ($save_render) {
		continue;
	}
	$widget = clone $widget;
	$width = $widget->option_integer("list_column_width", 2);
	$attributes = CSS::add_class('.col-sm-' . $width, $widget->context_class());
	$attributes = $this->object->apply_map($attributes); // TODO perhaps add a flag to avoid doing this when not needed??
	echo HTML::tag('div', $attributes, strval($widget->execute($this->object, true)));
}
