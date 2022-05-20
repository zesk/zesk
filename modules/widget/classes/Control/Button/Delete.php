<?php declare(strict_types=1);
namespace zesk;

class Control_Button_Delete extends Control_Button {
	/**
	 *
	 * @var ORM
	 */
	public $object = null;

	protected $options = [
		'nolabel' => true,
	];

	public function is_visible() {
		if ($this->object->is_new()) {
			return false;
		}
		return $this->user_can('delete', $this->object);
	}

	public function theme_variables() {
		/* @var $object ORM */
		$object = $this->object;
		$locale = $this->application->locale;
		$href = $this->option('href');
		if (!$href) {
			$href = $this->application->router()->get_route('delete', $object);
		}
		$class_name = $this->option('class_name', $object->class_name());
		$link_text = $locale->__('Delete {name}', [
			'name' => $class_name,
		]);
		$link_text = $this->option('button_label', $link_text);
		$title = $object->get($object->name_column());
		return [
			'href' => $href,
			'title' => $title,
			'data-confirm' => $locale->__('Are you sure you want to delete {name} "{title}"?', [
				'title' => $title,
				'name' => $class_name,
			]),
			'confirm' => $this->optionBool('confirm'),
			'link_text' => $link_text,
		] + parent::theme_variables();
	}

	public function render() {
		return $this->is_visible() ? parent::render() : '';
	}
}
