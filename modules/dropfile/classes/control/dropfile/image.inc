<?php
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Control_DropFile_Image extends Control_Content_Image {
	public function initialize() {
		parent::initialize();
	}

	/**
	 * Set URL upload path
	 *
	 * @param string $set
	 * @return Control_DropFile_Image|string
	 */
	public function url_upload($set = null) {
		return $set === null ? $this->option('url_upload') : $this->set_option('url_upload', $set);
	}

	public function dimensions($width = null, $height = null) {
		if ($width === null && $height === null) {
			return array(
				$this->option_integer('width'),
				$this->option_integer('height'),
			);
		}
		$this->set_option('height', $height === null ? null : intval($height));
		$this->set_option('width', $width === null ? null : intval($width));
		return $this;
	}

	public function theme_variables() {
		return array(
			'remove_enabled' => $this->remove_enabled(),
			'url_upload' => URL::query_append($this->url_upload(), array(
				"theme" => 'dropfile',
				"name" => $this->name,
				"width" => $this->option_integer('width'),
				"height" => $this->option_integer('height'),
			)),
		) + parent::theme_variables();
	}

	public function remove_enabled($set = null) {
		return $set === null ? $this->option_bool('remove_enabled') : $this->set_option('remove_enabled', to_bool($set));
	}
}
