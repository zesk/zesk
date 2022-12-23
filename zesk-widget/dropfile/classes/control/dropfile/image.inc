<?php declare(strict_types=1);
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Control_DropFile_Image extends Control_Content_Image {
	public function initialize(): void {
		parent::initialize();
	}

	/**
	 * Set URL upload path
	 *
	 * @param string $set
	 * @return Control_DropFile_Image|string
	 */
	public function url_upload($set = null) {
		return $set === null ? $this->option('url_upload') : $this->setOption('url_upload', $set);
	}

	public function dimensions($width = null, $height = null) {
		if ($width === null && $height === null) {
			return [
				$this->optionInt('width'),
				$this->optionInt('height'),
			];
		}
		$this->setOption('height', $height === null ? null : intval($height));
		$this->setOption('width', $width === null ? null : intval($width));
		return $this;
	}

	public function themeVariables(): array {
		return [
			'remove_enabled' => $this->remove_enabled(),
			'url_upload' => URL::queryAppend($this->url_upload(), [
				'theme' => 'dropfile',
				'name' => $this->name,
				'width' => $this->optionInt('width'),
				'height' => $this->optionInt('height'),
			]),
		] + parent::themeVariables();
	}

	public function remove_enabled($set = null) {
		return $set === null ? $this->optionBool('remove_enabled') : $this->setOption('remove_enabled', toBool($set));
	}
}
