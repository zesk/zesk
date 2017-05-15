<?php
namespace zesk;

class Control_Content_File extends Control_Widgets {
	/*
	 * @var Control_File
	 */
	private $_file_widget = null;

	function model() {
		return new Content_File();
	}
	function initialize() {
		$prefix = $this->name() . "_";
		$this->_file_widget = $w = $this->widget_factory("Control_File");
		$w->set_option(array(
			'hash_file' => true
		) + $this->options);
		$w->column = $prefix . "upload";
		// $this->child(widgets::control_text($prefix . "name"), __("Name"));

		$this->child($w);

		$w = $this->widget_factory("Control_Text")->names($prefix . "desc", __("Description"))->textarea(true);
		$this->child($w);
		$this->upload(true);

		parent::initialize();
	}
	/**
	 *
	 * @return Control_File
	 */
	function control_file() {
		return $this->_file_widget;
	}
	function submit() {
		if (!$this->submit_children()) {
			return $this->required() ? false : true;
		}
		$prefix = $this->name() . "_";
		$member = $this->value();
		if (!$member instanceof Content_File) {
			$member = $this->model();
		}
		try {
			$member->Content_Data = Content_Data::from_path($this->_file_widget->path(), false);
			$member->Name = $member->Original = $this->_file_widget->original_name();
			$member->MIMEType = MIME::from_filename($member->Original);
			$member->Description = $this->object->get($prefix . "desc");
			$member->store();
			$this->value($member);
			return true;
		} catch (Exception_File_NotFound $e) {
			if ($member->Content_Data === null) {
				$this->object->set($this->column(), null);
			}
			return $this->required() ? false : true;
		}
	}
}
