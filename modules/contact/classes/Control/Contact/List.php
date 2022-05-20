<?php declare(strict_types=1);
namespace zesk;

class Control_Contact_List extends Control_List {
	protected $class = 'zesk\\Contact';

	public function hook_widgets() {
		$widgets = [];

		$widgets[] = $this->widget_factory(View_Text::class)->names('name');

		return $widgets;
	}
}
