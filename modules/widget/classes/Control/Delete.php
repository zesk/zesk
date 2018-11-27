<?php
namespace zesk;

class Control_Delete extends Control_Edit {
	/**
	 * (non-PHPdoc)
	 * @see Control_Edit::initialize()
	 */
	protected function initialize() {
		parent::initialize();
		$this->options['submit_redirect_message'] = __('{class.name} "{object.name}" was deleted.');
		$title = __('Delete {class_name} &ldquo;{name}&rdquo;', array(
			"class_name" => $this->object->class_name(),
			"name" => $this->object->name(),
		));
		$this->set_option('title', $title);
	}

	public function submit() {
		if (!$this->submit_children()) {
			return false;
		}
		if ($this->object->reassign && method_exists($this->object, "reassign")) {
			$this->object->reassign($this->object->reassign);
		}
		$this->object->delete();
		return $this->submit_redirect();
	}
}
