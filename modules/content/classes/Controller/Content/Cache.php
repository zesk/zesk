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
	public static function cache_prefix(Configuration $configuration) {
		return $configuration->path_get(__CLASS__ . '::cache_prefix', '/cache/image/');
	}

	/**
	 *
	 * @param Content_Image $image
	 */
	public static function image_changed(Content_Image $image) {
		$app = $image->application;
		$path = path($app->document_root(), self::cache_prefix($app->configuration), $image->id());
		if (is_dir($path)) {
			Directory::delete_contents($path);
		}
	}

	/**
	 *
	 * @param unknown $image_file
	 * @param unknown $styles
	 */
	private function _correct_url_redirect($image_file, $styles) {
		$this->response->cache_for(60);
		$this->response->redirect($this->router->prefix() . $this->route->url_replace(array(
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
		return path(self::cache_prefix($image->application->configuration), $image->id(), $style, basename($image->path));
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
	public static function image_from_url(Application $application, $url) {
		$prefix = preg_quote(self::cache_prefix($application->configuration));
		if (preg_match('#^' . $prefix . '([0-9]+)/.*#', $url, $matches)) {
			try {
				$image = $application->orm_factory(Content_Image::class, $matches[1])->fetch();
				return $image;
			} catch (Exception_ORM_NotFound $e) {
			}
		}
		return null;
	}

	/**
	 *
	 * @param integer $id
	 * @param string $styles
	 * @param string $file
	 */
	protected function action_image($id, $styles = null, $file = null) {
		try {
			/* @var $image Content_Image */
			$image = $this->application->orm_factory(Content_Image::class, $id)->fetch();
			$image_file = basename($image->path());
			$commands = $this->parse_commands($image, $styles);
			if ($file === null || $file !== $image_file || empty($styles) || $commands === null) {
				if ($commands === null || empty($styles)) {
					$styles = self::image_variation_default;
				}
				return $this->_correct_url_redirect($image_file, $styles);
			}
			$image_data = $image->data;
			if (!$image_data instanceof Content_Data) {
				$this->response->status(404, "Not Found 1");
				$this->response->cache_for(60, Response::CACHE_PATH);
				return;
			}
			$data = $image_data->data();
			if ($commands) {
				$data = $this->apply_commands($commands, $data);
			}
			$this->request_to_file($data);
		} catch (Exception_ORM_NotFound $e) {
			$this->response->status(404, "Not Found 2");
			$this->response->cache_for(60, Response::CACHE_PATH);
			return;
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
			} elseif (empty($height)) {
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
			$new_data = $this->call_hook_arguments("image_" . $hook, array(
				$command
			), null);
			if ($new_data) {
				$data = $new_data;
			}
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
		return Image_Library::factory($this->application)->image_scale_data($command['data'], array(
			'zoom' => true,
			'width' => $width,
			'height' => $height
		));
	}

	/**
	 *
	 * {@inheritdoc}
	 *
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
