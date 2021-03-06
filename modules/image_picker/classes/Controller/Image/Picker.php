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
class Controller_Image_Picker extends \zesk\Controller_ORM {
	protected $class = Content_Image::class;

	/**
	 *
	 * {@inheritDoc}
	 * @see Controller_ORM::_action_default()
	 */
	public function _action_default($action = null, $object = null, $options = array()) {
		$control = $this->widget_factory(Control_Picker_Content_Image::class)->names('image');
		return $this->control($control, $object);
	}

	const OPTION_UPLOAD_THEME_MAP = 'upload_theme_map';

	public function upload_theme_map(array $set = null) {
		return $set === null ? $this->option_array(self::OPTION_UPLOAD_THEME_MAP, array()) + $this->application->configuration->path_get([
			__CLASS__,
			self::OPTION_UPLOAD_THEME_MAP,
		], []) : $this->set_option(self::OPTION_UPLOAD_THEME_MAP, $set);
	}

	public function action_upload() {
		$request = $this->request;
		$file = null;
		$locale = $this->application->locale;

		try {
			$file = $request->file($this->request->get('name', 'file'));
		} catch (Exception_Upload $e) {
			$message = $e->getMessage();
			if (strpos($message, 'upload_max_filesize')) {
				$max_size = ini_get('upload_max_filesize');
				$real_size = to_bytes($max_size);
				$this->json(array(
					'status' => true,
					'message' => $locale->__('That file is too large. You can only upload files which are less than {bytes}.', array(
						'bytes' => $this->application->theme('bytes', array(
							"content" => $real_size,
						)),
					)),
				));
				return;
			}
		}
		if (!$file) {
			$this->json(array(
				"status" => false,
				"message" => $locale->__("No file uploaded."),
			));
			return;
		}
		$fix_result = Content_Image::correct_orientation($this->application, $file['tmp_name']);
		$image = Content_Image::register_from_file($this->application, $file['tmp_name'], array(
			"path" => $file['name'],
		));
		if (!$image) {
			$this->json(array(
				"status" => false,
				"message" => $locale->__("Failed to save new image."),
			));
			return;
		}
		if ($fix_result !== true) {
			$this->application->logger->warning("Unable to fix orientation of image {id}: {name}", array(
				'id' => $image->id(),
				'name' => $file['name'],
			));
		}
		if ($image) {
			$image->users = $this->user;
			$image->store();
		}
		$theme_key = $this->request->get('theme', '');
		$theme = avalue($this->upload_theme_map(), $theme_key, 'zesk/control/picker/content/image/item');
		$this->json(array(
			"status" => true,
			"content" => $this->application->theme($theme, array(
				"object" => $image,
				"width" => $this->request->geti("width"),
				"height" => $this->request->geti("height"),
				"name" => $this->request->get("name"),
			)),
			"message" => $locale->__("Upload successful."),
		));
	}

	public function action_image_delete(Content_Image $image) {
		$locale = $this->application->locale;
		if ($this->user->can('delete', $image)) {
			$image->delete();
			return $this->json(array(
				'message' => $locale->__('Image was deleted.'),
				'status' => true,
			));
		}
		return $this->json(array(
			'message' => $locale->__('You do not have permission to delete that image.'),
			'status' => false,
		));
	}
}
