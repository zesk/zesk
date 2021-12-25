<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage content
 * @author kent
 * @copyright &copy; 2017 Market Acumen, Inc.
 */
namespace zesk;

/**
 * @author kent
 */
class Control_Content_File extends Control_Widgets {
	/*
	 * @var Control_File
	 */
	private $_file_widget = null;

	public function model() {
		return $this->application->orm_factory(Content_File::class);
	}

	public function initialize(): void {
		$prefix = $this->name() . "_";
		$this->_file_widget = $w = $this->widget_factory("Control_File");
		$w->set_option([
			'hash_file' => true,
		] + $this->options);
		$w->column = $prefix . "upload";

		$this->child($w);

		$locale = $this->application->locale;
		$w = $this->widget_factory(Control_Text::class)->names($prefix . "desc", $locale->__("Description"))->textarea(true);
		$this->child($w);
		$this->upload(true);

		parent::initialize();
	}

	/**
	 *
	 * @return Control_File
	 */
	public function control_file() {
		return $this->_file_widget;
	}

	public function submit() {
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
