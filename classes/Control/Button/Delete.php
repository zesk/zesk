<?php
namespace zesk;

class Control_Button_Delete extends Control_Button {
	protected $options = array(
		'nolabel' => true
	);
	function is_visible() {
		if ($this->object->is_new()) {
			return false;
		}
		return $this->user_can("delete", $this->object);
	}
	function theme_variables() {
		/* @var $object Object */
		$object = $this->object;
		$href = $this->option('href');
		if (!$href) {
			$href = $this->application->router()->get_route("delete", $object);
		}
		$class_name = $this->option('class_name', $object->class_name());
		$link_text = __("Delete {name}", array(
			"name" => $class_name
		));
		$link_text = $this->option('button_label', $link_text);
		$title = $object->get($object->name_column());
		return array(
			'href' => $href,
			'title' => $title,
			'data-confirm' => __('Are you sure you want to delete {name} "{title}"?', array(
				"title" => $title,
				"name" => $class_name
			)),
			'confirm' => $this->option_bool('confirm'),
			'link_text' => $link_text
		) + parent::theme_variables();
	}
	public function render() {
		return $this->is_visible() ? parent::render() : "";
	}
}
