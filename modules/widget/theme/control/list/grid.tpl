<?php declare(strict_types=1);
/**
 * @copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

foreach ($this->widgets as $widget) {
	/* @var $widget Widget */
	if (!$widget->is_visible()) {
		continue;
	}
	$save_render = $widget->saveRender();
	if ($save_render) {
		continue;
	}
	$widget = clone $widget;
	$width = $widget->optionInt('list_column_width', 2);
	$attributes = CSS::addClass('.col-sm-' . $width, $widget->contextClass());
	$attributes = $this->object->applyMap($attributes); // TODO perhaps add a flag to avoid doing this when not needed??
	echo HTML::tag('div', $attributes, strval($widget->execute($this->object, true)));
}
