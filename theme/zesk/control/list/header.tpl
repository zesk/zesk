<?php
echo $this->title_prefix;
if ($this->title) {
	echo html::tag('h1', $this->title);
}
echo $this->title_suffix;
if ($this->help) {
	echo html::tag('p', '.help', $this->help);
}
echo $this->filter_prefix;
if ($this->filter && $this->show_filter) {
	echo $this->filter->render();
	$this->filter->content = $this->filter->content_children = "";
}
echo $this->filter_suffix . $this->pager_prefix;
if ($this->pager && $this->show_pager) {
	echo $this->pager->render();
}
echo $this->pager_suffix;

if ($this->render_header_widgets) {
	if ($this->empty_list_hide_header && $this->list_is_empty) {
		// No header
	} else {
		$list_column_count = $this->geti('list_column_count', 12);
		echo $this->header_prefix;
		$header_widgets = $this->header_widgets;
		echo html::div_open('.row header');
		foreach ($header_widgets as $widget) {
			/* @var $widget Widget */
			if ($widget->is_visible()) {
				echo html::tag('div', css::add_class(".col-xs-$list_column_count .col-sm-" . $widget->option('list_column_width', 2), $widget->context_class()), $widget->render());
			}
		}
		echo html::div_close();
		echo $this->header_suffix;
	}
}