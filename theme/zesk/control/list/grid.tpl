<?php
echo html::div_open('.row .odd-' . $this->odd);
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
	echo html::tag('div', css::add_class('.col-sm-' . $width, $widget->context_class()), strval($widget->execute($this->object, true)));
}
echo html::div_close();
