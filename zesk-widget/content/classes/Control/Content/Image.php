<?php
declare(strict_types=1);
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
	public function initialize(): void {
		$this->setUpload(true);
		parent::initialize();
	}

	/**
	 * @return array
	 */
	public function allowedMimeTypes(): array {
		return $this->optionArray('allowed_mime_types', [
			'image/jpeg',
			'image/gif',
			'image/png',
		]);
	}

	/**
	 * @param array|string $set
	 * @return $this
	 */
	public function setAllowedMimeType(array|string $set): self {
		return $this->setOption('allowed_mime_types', toList($set));
	}

	/**
	 * @return ORM
	 */
	protected function model(): ORM {
		return new Content_Image($this->application);
	}

	protected function normalize() {
		$value = $this->value();
		if (is_numeric($value)) {
			try {
				$value = $this->application->ormFactory(Content_Image::class, $value)->fetch();
			} catch (Exception_ORM_NotFound $e) {
				return null;
			}
		} elseif (!$value instanceof Content_Image) {
			return null;
		}
		if (!$this->userCan('edit', $value)) {
			return null;
		}
		return $value;
	}

	protected function load(): void {
		parent::load();
		$this->value($this->normalize());
	}

	protected function defaults(): void {
		$this->value($this->normalize());
	}

	/**
	 * @return bool
	 * @throws Exception_Key
	 * @see Widget::validate()
	 */
	protected function validate(): bool {
		try {
			$data = $this->request->file($this->name());
		} catch (Exception_Upload $e) {
			$code = $e->getCode();
			if ($code === UPLOAD_ERR_NO_FILE) {
				return $this->validate_required();
			}
			$this->setOption('exception', $e);
			$this->error($e->getMessage());
			return false;
		}
		$name = $data['name'] ?? '';
		$type = $data['type'] ?? '';
		$allowed = $this->allowedMimeTypes();
		if (!in_array($type, $allowed)) {
			$type = MIME::from_filename($name);
			if (!in_array($type, $allowed)) {
				$this->error($this->locale()->__('Not allowed to upload files of that type.'));
				return false;
			}
		}
		return true;
	}

	/**
	 * (non-PHPdoc)
	 * @see Widget::submit()
	 */
	public function submit(): bool {
		try {
			$data = $this->request->file($this->name());
			extract($data, EXTR_IF_EXISTS);
			$name = $data['name'] ?? '';
			$tmp_name = $data['tmp_name'] ?? '';
			// $type = $data['type'] ?? null;
			// $size = $data['size'] ?? -1;
			$image = Content_Image::registerFromFile($this->application, $tmp_name, [
				'path' => File::name_clean($name),
			]);
			$this->value($image->id());
		} catch (Exception_Upload $e) {
			$this->application->logger->debug('Upload exception {1}, Upload dir: {0}', [
				ini_get('upload_tmp_dir'),
				$e,
			]);
		}

		return parent::submit();
	}

	public function themeVariables(): array {
		return [
			'width' => $this->optionInt('width', 200),
			'height' => $this->optionInt('height', 200),
			'value' => $this->normalize(),
		] + parent::themeVariables();
	}
}
