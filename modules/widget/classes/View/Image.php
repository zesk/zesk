<?php declare(strict_types=1);

/**
 * This needs to be simplified greatly.
 * Too many options, needs to support most common case easily.
 * Add setter/getters for relative path options, etc.
 *
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

class View_Image extends View {
	public static $debug = false;

	public function initialize(): void {
		parent::initialize();
		self::$debug = $this->option('debug');
	}

	public static function debug($set = null) {
		if (is_bool($set)) {
			self::$debug = $set;
			return;
		}
		return self::$debug;
	}

	protected function source_directory() {
		return $this->option('root_directory', $this->application->document_root());
	}

	protected function cache_directory() {
		$directory = $this->option('cache_directory', path($this->application->document_root(), '/cache/images/'));
		return $this->application->paths->expand($directory);
	}

	protected function cache_url_prefix() {
		$prefix = $this->option('cache_url_prefix', null);
		if ($prefix) {
			return $prefix;
		}
		$cache = realpath($this->cache_directory());
		$doc_root = realpath($this->application->document_root());
		return StringTools::removePrefix($cache, $doc_root);
	}

	private function debug_log($message): void {
		if (self::$debug) {
			$this->application->logger->debug($message);
		}
	}

	private function scale_image($source) {
		[$width, $height] = getimagesize($source);
		extract($this->options, EXTR_IF_EXISTS);

		/*
		 * Deal with the file paths
		 */
		$source = realpath($source);
		$this->debug_log("\$sourceFile = $source");
		$this->debug_log('$this->source_directory()  = ' . $this->source_directory());

		$cache = $this->cache_directory();

		try {
			Directory::depend($cache, 0o755);
		} catch (Exception_Directory_Create $e) {
			$this->application->logger->error($e);
			return $this->missing_file();
		}

		$prefix = '';
		if ($this->hasOption('id')) {
			$prefix = $this->option('id') . '-';
		}
		$target_filename = $prefix . File::base($source) . "-${width}x${height}." . File::extension($source);

		$target_full_path = path($cache, $target_filename);
		$scaled_result = path($this->cache_url_prefix(), $target_filename);

		if (self::$debug) {
			$this->application->logger->debug('$scaled_result = {scaled_result}', compact('scaled_result'));
		}
		$this->setOption('scale_path', $target_full_path);
		$this->call_hook('scale_path', $target_full_path);
		if (self::$debug) {
			$this->application->logger->debug("\$target_full_path is $target_full_path");
		}
		if (!$this->optionBool('always_generate') && file_exists($target_full_path)) {
			[$this->width, $this->height] = getimagesize($target_full_path);
			return $scaled_result;
		}

		if (Image_Library::factory($this->application)->image_scale($source, $target_full_path, $this->options)) {
			[$this->width, $this->height] = getimagesize($target_full_path);
			$this->setOption('created_file', true);
		}
		return $scaled_result;
	}

	private static function missing_file(): void {
	}

	private function relative_to_absolute_path($path) {
		if ($path[0] === '/') {
			return HTML::href($this->application, $path);
		}
		$wd = path(str_replace('\\', '/', dirname($_SERVER['SCRIPT_FILENAME'])), $path);
		return $wd;
	}

	private function missing_image_path() {
		return $this->option('missing_image_path', $this->application->url('/share/zesk/images/missing.gif'));
	}

	/**
	 * Returns the representation of model as an <img /> tag.
	 *
	 * @return string
	 */
	public function render(): string {
		$object = $this->object;
		$actualWidth = $object->get($this->option('WidthColumn', 'Width'));
		$actualHeight = $object->get($this->option('HeightColumn', 'Height'));

		$value = $object->applyMap($this->option('src', '{src}'));
		//avalue($object, $this->column('src'));

		if (empty($value)) {
			$this->debug_log('Empty value for src...');
			return '';
		}

		if ($this->optionBool('is_relative', true)) {
			$file_path = $this->relative_to_absolute_path($value);
			$path = $file_path;
		} else {
			$file_path = $value;
			$path = path($this->source_directory(), $file_path);
		}

		if (!file_exists($path) || is_dir($path)) {
			$this->debug_log("Image path not found: $path");
			return $this->output_image($object, $file_path, $this->missing_image_path(), "\n<!-- Not a file -->");
		}
		$ext = strtolower(File::extension($path, false));
		if (!in_array($ext, [
			'jpg',
			'jpeg',
			'gif',
			'png',
		])) {
			return $this->output_image($object, $file_path, $this->missing_image_path(), "\n<!-- NOT AN IMAGE? $ext -->");
		}

		$scale_value = $this->scale_image($path);
		if (is_string($scale_value)) {
			$value = $scale_value;
		}
		if (self::$debug) {
			$this->application->logger->notice("Output image path is file_path=$file_path value=$value");
		}
		return $this->output_image($file_path, $value);
	}

	protected function output_image($file_path, $value, $options = '') {
		$attrs = [];

		$attrs['width'] = $this->optionInt('ScaledWidth', $this->optionInt('Width'));
		$attrs['height'] = $this->optionInt('ScaledHeight', $this->optionInt('Height'));
		$attrs['border'] = $this->optionInt('Border', 0);
		$attrs['align'] = $this->option('align', null);
		$attrs['style'] = $this->option('style', null);
		$attrs['class'] = $this->option('class', null);
		$attrs['alt'] = $this->option('alt', '');
		$attrs['title'] = $this->option('title', false);

		$attrs = $this->object->applyMap($attrs);
		if ($this->hasOption('image_host')) {
			$attrs['src'] = $this->option('image_host') . $file_path;
		} else {
			$attrs['src'] = $value;
		}
		$attrs['src'] = str_replace(' ', '%20', $attrs['src']);
		if ($this->hasOption('query')) {
			$attrs['src'] = URL::queryFormat($attrs['src'], $this->option('query'));
		}

		$this->setOption('scale_src', $attrs['src']);

		$result = HTML::tag('img', $attrs, null);

		return $result . $options;
	}

	public function didCreateFile() {
		return $this->optionBool('created_file', true);
	}

	/**
	 * Generate a widget with appropriate options and return it.
	 *
	 * @param string $src
	 *        	Path to image to scale
	 * @param int $width
	 * @param int $height
	 * @param string $alt
	 * @param array $options
	 * @return View_Image
	 */
	public static function scaled_widget(Application $application, $width = false, $height = false, $alt = '', array $options = []) {
		if ($width) {
			$options['width'] = $width;
		}
		if ($height) {
			$options['height'] = $height;
		}
		if ($alt) {
			$options['alt'] = $alt;
		}
		$w = new View_Image($application, $options);
		$w->request($application->request() ?? Request::factory($application, 'http://test/'));
		$w->response($application->responseFactory($w->request()));
		return $w;
	}

	public static function scaled(Application $application, $src, $width = false, $height = false, $alt = '', array $options = []) {
		$w = self::scaled_widget($application, $width, $height, $alt, $options);
		$x = new Model($application);
		$x->src = $src;
		return $w->execute($x);
	}

	public static function scaled_path(Application $application, $src, $width = false, $height = false, $alt = '', array $options = []) {
		$w = self::scaled_widget($application, $width, $height, $alt, $options);
		$x = new Model($application);
		$x->src = $src;
		$w->execute($x);
		return $w->option('scale_src');
	}
}
