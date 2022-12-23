<?php declare(strict_types=1);
namespace zesk;

class Control_Button_Delete extends Control_Button {
	/**
	 *
	 * @var ORMBase
	 */
	public $object = null;

	protected $options = [
		'nolabel' => true,
	];

	public function is_visible() {
		if ($this->object->isNew()) {
			return false;
		}
		return $this->userCan('delete', $this->object);
	}

	public function themeVariables(): array {
		/* @var $object ORMBase */
		$object = $this->object;
		$locale = $this->application->locale;
		$href = $this->option('href');
		if (!$href) {
			$href = $this->application->router()->getRoute('delete', $object);
		}
		$class_name = $this->option('class_name', $object->className());
		$link_text = $locale->__('Delete {name}', [
			'name' => $class_name,
		]);
		$link_text = $this->option('button_label', $link_text);
		$title = $object->get($object->nameColumn());
		return [
			'href' => $href,
			'title' => $title,
			'data-confirm' => $locale->__('Are you sure you want to delete {name} "{title}"?', [
				'title' => $title,
				'name' => $class_name,
			]),
			'confirm' => $this->optionBool('confirm'),
			'link_text' => $link_text,
		] + parent::themeVariables();
	}

	public function render(): string {
		return $this->is_visible() ? parent::render() : '';
	}
}
