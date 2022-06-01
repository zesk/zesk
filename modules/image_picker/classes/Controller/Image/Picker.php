<?php declare(strict_types=1);
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
	protected string $class = Content_Image::class;

	/**
	 *
	 * {@inheritDoc}
	 * @see Controller_ORM::_action_default()
	 */
	public function _action_default($action = null, $object = null, $options = []) {
		$control = $this->widget_factory(Control_Picker_Content_Image::class)->names('image');
		return $this->control($control, $object);
	}

	public const OPTION_UPLOAD_THEME_MAP = 'upload_theme_map';

	public function upload_theme_map(array $set = null) {
		return $set === null ? $this->optionArray(self::OPTION_UPLOAD_THEME_MAP, []) + $this->application->configuration->path_get([
			__CLASS__,
			self::OPTION_UPLOAD_THEME_MAP,
		], []) : $this->setOption(self::OPTION_UPLOAD_THEME_MAP, $set);
	}

	public function action_upload(): void {
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
				$this->json([
					'status' => true,
					'message' => $locale->__('That file is too large. You can only upload files which are less than {bytes}.', [
						'bytes' => $this->application->theme('bytes', [
							'content' => $real_size,
						]),
					]),
				]);
				return;
			}
		}
		if (!$file) {
			$this->json([
				'status' => false,
				'message' => $locale->__('No file uploaded.'),
			]);
			return;
		}
		$fix_result = Content_Image::correct_orientation($this->application, $file['tmp_name']);
		$image = Content_Image::registerFromFile($this->application, $file['tmp_name'], [
			'path' => $file['name'],
		]);
		if (!$image) {
			$this->json([
				'status' => false,
				'message' => $locale->__('Failed to save new image.'),
			]);
			return;
		}
		if ($fix_result !== true) {
			$this->application->logger->warning('Unable to fix orientation of image {id}: {name}', [
				'id' => $image->id(),
				'name' => $file['name'],
			]);
		}
		if ($image) {
			$image->users = $this->user;
			$image->store();
		}
		$theme_key = $this->request->get('theme', '');
		$theme = avalue($this->upload_theme_map(), $theme_key, 'zesk/control/picker/content/image/item');
		$this->json([
			'status' => true,
			'content' => $this->application->theme($theme, [
				'object' => $image,
				'width' => $this->request->getInt('width'),
				'height' => $this->request->getInt('height'),
				'name' => $this->request->get('name'),
			]),
			'message' => $locale->__('Upload successful.'),
		]);
	}

	public function action_image_delete(Content_Image $image) {
		$locale = $this->application->locale;
		if ($this->user->can('delete', $image)) {
			$image->delete();
			return $this->json([
				'message' => $locale->__('Image was deleted.'),
				'status' => true,
			]);
		}
		return $this->json([
			'message' => $locale->__('You do not have permission to delete that image.'),
			'status' => false,
		]);
	}
}
