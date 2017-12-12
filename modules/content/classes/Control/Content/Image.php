<?php
/**
 * 
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Control_Content_Image extends Control {
	public function initialize() {
		$this->upload(true);
		parent::initialize();
	}
	public function allowed_mime_types($set = null) {
		if ($set !== null) {
			$this->set_option("allowed_mime_types", to_list($set));
			return $this;
		}
		return $this->option_array("allowed_mime_types", array(
			"image/jpeg",
			"image/gif",
			"image/png"
		));
	}
	protected function model() {
		return new Content_Image();
	}
	protected function normalize() {
		$value = $this->value();
		if (is_numeric($value)) {
			try {
				$value = $this->orm_factory('zesk\Content_Image', $value)->fetch();
			} catch (Exception_ORM_NotFound $e) {
				return null;
			}
		} else if (!$value instanceof Content_Image) {
			return null;
		}
		if ($value === null || !$this->user_can("edit", $value)) {
			return null;
		}
		return $value;
	}
	protected function load() {
		parent::load();
		$this->value($this->normalize());
	}
	protected function defaults() {
		$this->value($this->normalize());
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Widget::validate()
	 */
	protected function validate() {
		try {
			$data = $this->request->file($this->name());
		} catch (Exception_Upload $e) {
			$code = $e->getCode();
			if ($code === UPLOAD_ERR_NO_FILE) {
				return $this->validate_required();
			}
			$this->set_option('exception', $e);
			$this->error($e->getMessage());
			return false;
		}
		if ($data === null) {
			return $this->validate_required();
		}
		$name = $type = $size = $tmp_name = null;
		extract($data, EXTR_IF_EXISTS);
		
		$allowed = $this->allowed_mime_types();
		if (!in_array($type, $allowed)) {
			$type = MIME::from_filename($name);
			if (!in_array($type, $allowed)) {
				$this->error(__("Not allowed to upload files of that type."));
				return false;
			}
		}
		return true;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Widget::submit()
	 */
	public function submit() {
		try {
			$data = $this->request->file($this->name());
			if ($data === null) {
				return;
			}
			$name = $type = $size = $tmp_name = null;
			extract($data, EXTR_IF_EXISTS);
			
			$image = Content_Image::register_from_file($this->application, $tmp_name, array(
				'path' => File::name_clean($name)
			));
			$this->value($image->id());
		} catch (Exception_Upload $e) {
			$this->application->logger->debug("Upload exception {1}, Upload dir: {0}", array(
				ini_get('upload_tmp_dir'),
				$e
			));
		}
		
		return parent::submit();
	}
	public function theme_variables() {
		return array(
			'width' => $this->option_integer('width', 200),
			'height' => $this->option_integer('height', 200),
			'value' => $this->normalize()
		) + parent::theme_variables();
	}
}
