<?php
/**
 *
 */
use zesk\Content_Image;
use zesk\Exception_Upload;

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
	function _action_default($action = null, $object = null, $options = array()) {
		$control = $this->widget_factory(Control_Picker_Content_Image::class)->names('image');
		return $this->control($control, $object);
	}
	function upload_theme_map(array $set = null) {
		return $set === null ? $this->option_array('upload_theme_map', array()) : $this->set_option('upload_theme_map', $set);
	}
	function action_upload() {
		$request = $this->request;
		$file = null;
		try {
			$file = $request->file($this->request->get('name', 'file'));
		} catch (Exception_Upload $e) {
			$message = $e->getMessage();
			if (strpos($message, 'upload_max_filesize')) {
				$max_size = ini_get('upload_max_filesize');
				$real_size = to_bytes($max_size);
				$this->json(array(
					'status' => true,
					'message' => __('That file is too large. You can only upload files which are less than {bytes}.', array(
						'bytes' => $this->application->theme('bytes', array(
							"content" => $real_size
						))
					))
				));
				return;
			}
		}
		if (!$file) {
			$this->json(array(
				"status" => false,
				"message" => __("No file uploaded.")
			));
			return;
		}
		$fix_result = Content_Image::correct_orientation($this->application, $file['tmp_name']);
		$image = Content_Image::register_from_file($this->application, $file['tmp_name'], array(
			"path" => $file['name']
		));
		if (!$image) {
			$this->json(array(
				"status" => false,
				"message" => __("Failed to save new image.")
			));
			return;
		}
		if ($fix_result !== true) {
			$this->application->logger->warning("Unable to fix orientation of image {id}: {name}", array(
				'id' => $image->id(),
				'name' => $file['name']
			));
		}
		if ($image) {
			$image->users = $this->user;
			$image->store();
		}
		$theme_key = $this->request->get('theme', '');
		$theme = avalue($this->upload_theme_map(), $theme_key, 'control/picker/content/image/item');
		$this->json(array(
			"status" => true,
			"content" => $this->application->theme($theme, array(
				"object" => $image,
				"width" => $this->request->geti("width"),
				"height" => $this->request->geti("height"),
				"name" => $this->request->get("name")
			)),
			"message" => __("Upload successful.")
		));
	}
	function action_image_delete(Content_Image $image) {
		if ($this->user->can('delete', $image)) {
			$image->delete();
			return $this->json(array(
				'message' => __('Image was deleted.'),
				'status' => true
			));
		}
		return $this->json(array(
			'message' => __('You do not have permission to delete that image.'),
			'status' => false
		));
	}
}
