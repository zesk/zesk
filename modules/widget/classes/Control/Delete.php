<?php declare(strict_types=1);

namespace zesk;

class Control_Delete extends Control_Edit {
	/**
	 * (non-PHPdoc)
	 * @see Control_Edit::initialize()
	 */
	protected function initialize(): void {
		parent::initialize();
		$locale = $this->application->locale;
		$this->options['submit_redirect_message'] = $locale->__('{class.name} "{object.name}" was deleted.');
		$title = $locale->__('Delete {class_name} &ldquo;{name}&rdquo;', [
			'class_name' => $this->object->class_name(),
			'name' => $this->object->name(),
		]);
		$this->setOption('title', $title);
	}

	public function submit(): bool {
		if (!$this->submit_children()) {
			return false;
		}
		if ($this->object->reassign && method_exists($this->object, 'reassign')) {
			$this->object->reassign($this->object->reassign);
		}
		$this->object->delete();
		return $this->submit_redirect();
	}
}
