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
class Controller_Content_Cache extends Controller_Cache {
	/**
	 * 
	 * @var string
	 */
	const image_variation_default = "default";
	
	/**
	 * 
	 * @return mixed|mixed[]|\zesk\Configuration
	 */
	public static function cache_prefix() {
		global $zesk;
		/* @var $zesk \zesk\Kernel */
		return $zesk->configuration->path_get(__CLASS__ . '::cache_prefix', '/cache/image/');
	}
	
	/**
	 * 
	 * @todo Use app()->document_cache()?
	 * 
	 * @param Content_Image $image
	 */
	public static function image_changed(Content_Image $image) {
		/* @var $zesk zesk\Kernel */
		$path = path(app()->document_root(), self::cache_prefix(), $image->id());
		if (is_dir($path)) {
			Directory::delete_contents($path);
		}
	}
	private function _correct_url_redirect($image_file, $styles) {
		$this->response->cache_for(60);
		$this->response->redirect($this->router->url_replace(array(
			"file" => $image_file,
			"styles" => $styles
		)));
		return;
	}
	
	/**
	 * Return the url for an image
	 *
	 * @param Content_Image $image
	 * @param Router $router
	 * @return string
	 */
	public static function url_content_image(Content_Image $image, $style = null) {
		if ($style === null) {
			$style = self::image_variation_default;
		}
		return path(self::cache_prefix(), $image->id(), $style, basename($image->path));
	}
	
	/**
	 * 
	 * @param Content_Image $image
	 * @param unknown $width
	 * @param unknown $height
	 * @return string
	 */
	public static function url_content_image_scaled(Content_Image $image, $width = null, $height = null) {
		$style = "c${width}x${height}";
		return self::url_content_image($image, $style);
	}
	
	/**
	 * 
	 * @param string $url
	 * @return Content_Image|null
	 */
	public static function image_from_url($url) {
		$prefix = preg_quote(self::cache_prefix());
		if (preg_match('#^' . $prefix . '([0-9]+)/.*#', $url, $matches)) {
			try {
				$image = Object::factory('zesk\\Content_Image', $matches[1])->fetch();
				return $image;
			} catch (Exception_Object_NotFound $e) {
			}
		}
		return null;
	}
	
	/**
	 *
	 *
	 * @param integer $id
	 * @param string $styles
	 * @param string $file
	 */
	protected function action_image($id, $styles = null, $file = null) {
		try {
			/* @var $image Content_Image */
			$image = $this->object_factory('zesk\\Content_Image', $id)->fetch();
			$image_file = basename($image->path());
			$commands = $this->parse_commands($image, $styles);
			if ($file === null || $file !== $image_file || empty($styles) || $commands === null) {
				if ($commands === null || empty($styles)) {
					$styles = self::image_variation_default;
				}
				return $this->_correct_url_redirect($image_file, $styles);
			}
			$data = $image->data->data();
			if ($commands) {
				$data = $this->apply_commands($commands, $data);
			}
			$this->request_to_file($data);
		} catch (Exception_Object_NotFound $e) {
			$this->response->status(404, "Not Found");
			$this->response->cache_for(60, Response::cache_path);
		}
	}
	
	/**
	 *
	 * @param unknown $styles
	 * @return array|null
	 */
	protected function parse_commands(Content_Image $image, $styles) {
		if (empty($styles) || $styles === "default") {
			return array();
		}
		if (preg_match('/c([0-9]*)x([0-9]*)/', $styles, $matches)) {
			$width = $matches[1] ? intval($matches[1]) : null;
			$height = $matches[2] ? intval($matches[2]) : null;
			if (empty($width)) {
				if (empty($height)) {
					return null;
				}
				$width = round(($image->width / $image->height) * $height);
			} else if (empty($height)) {
				$height = round(($image->height / $image->width) * $width);
			}
			return array(
				array(
					"hook" => "scale",
					"width" => intval($width),
					"height" => intval($height)
				)
			);
		}
		return null;
	}
	
	/**
	 * 
	 * @param array $commands
	 * @param unknown $data
	 * @return mixed|NULL|string|\zesk\NULL
	 */
	protected function apply_commands(array $commands, $data) {
		$original = $data;
		foreach ($commands as $command) {
			$hook = avalue($command, "hook");
			if (!$hook) {
				continue;
			}
			$command['original'] = $original;
			$command['data'] = $data;
			$data = $this->call_hook_arguments("image_" . $hook, array(
				$command
			), $data);
		}
		return $data;
	}
	
	/**
	 * 
	 * @param array $command
	 * @return string
	 */
	protected function hook_image_scale(array $command) {
		$width = $height = null;
		extract($command, EXTR_IF_EXISTS);
		return Image_Library::singleton()->image_scale_data($command['data'], array(
			'zoom' => true,
			'width' => $width,
			'height' => $height
		));
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Controller::get_route_map()
	 */
	public function get_route_map($action = null, $object = null, $options = null) {
		if ($action === "image") {
			$width = $height = $styles = null;
			extract($options, EXTR_IF_EXISTS);
			if ($styles === null) {
				if ($width || $height) {
					$styles = "c${width}x${height}";
				} else {
					$styles = "default";
				}
			}
			return array(
				'styles' => "$styles",
				'file' => basename($object->path())
			);
		}
		return array();
	}
}
