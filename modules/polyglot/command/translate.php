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
class Command_Translate extends Command_Base {
	/**
	 *
	 * @var integer
	 */
	public const error_parameters = 1;

	/**
	 *
	 * @var integer
	 */
	public const error_service_translate = 2;

	/**
	 *
	 * @var integer
	 */
	public const error_file_formats = 3;

	/**
	 *
	 * @var array
	 */
	public array $option_types = [
		'source' => 'string',
		'target' => 'string',
		'interactive' => 'boolean',
		'list' => 'boolean',
		'destination' => 'dir',
		'language-file' => 'file',
		'interactive' => 'boolean',
	];

	/**
	 *
	 * @var array
	 */
	public $option_help = [
		'source' => 'Source ',
		'target' => 'string',
		'interactive' => 'boolean',
		'list' => 'boolean',
	];

	/**
	 *
	 * @var Service_Translate
	 */
	protected $service_object;

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Command::run()
	 */
	protected function run() {
		$app = $this->application;
		$source_language_file = $this->option('language-file', $app->configuration->getPath('Module_PolyGlot::source_file'));
		if (!$source_language_file) {
			$this->usage('Need a source --language-file to determine source strings');
		}
		$destination = $this->option('destination', $app->configuration->getPath(Locale::class . '::auto_path'));
		if (!$destination) {
			$this->usage('Need a directory --destination {destination} is not a directory');
		}
		if (!is_dir($destination)) {
			$this->usage('Need a directory "{destination}" is not a directory', compact('destination'));
		}
		$classes = Service::serviceClasses($app, 'translate');
		if ($this->optionBool('list')) {
			echo ArrayTools::suffixKeys($classes, "\n");
			return 0;
		}
		$target_language = $this->option('target');
		if (!$target_language) {
			$this->error('Please supply a --target language');
			return self::error_parameters;
		}
		$source_language = $this->option('source', $app->locale->language());

		$target_language = strtolower($target_language);
		$source_language = strtolower($source_language);

		$default_class = first($classes);
		/* @var $service_object Service_Translate */
		try {
			$service_object = $this->service_object = Service_Translate::factory_translate($app, $target_language, $source_language);
		} catch (Exception $e) {
			$this->error($e->getMessage(), $e->arguments);
			return 2;
		}
		$this->verboseLog('Using default service {class}', [
			'class' => $service_object::class,
		]);
		$target_file = path($destination, "$target_language.inc");

		if (!is_file($target_file)) {
			$target_file = file_put_contents($target_file, map('<' . "?php\n// Generated file by {class}, editing OK\n\$tt = array();\n\nreturn \$tt;\n", ['class' => $default_class]));
		}
		$target_translations = $app->load($target_file);
		if (!is_array($target_translations)) {
			$this->error('target file {target_file} does not return a translation table', compact('target_file'));
			return self::error_file_formats;
		}
		$translation_file = $app->load($source_language_file);
		if (!is_array($translation_file)) {
			$this->error('translation file {translation_file} does not return a translation table', compact('translation_file'));
			return self::error_file_formats;
		}
		if (count($translation_file) === 0) {
			$this->error('translation file {translation_file} is empty', compact('translation_file'));
			return self::error_file_formats;
		}

		$tt = [];
		foreach ($translation_file as $key => $phrase) {
			$mapping = $this->preprocess_phrase($phrase);
			$translation = $service_object->translate($phrase);
			$translation = $this->postprocess_phrase($translation, $mapping);

			$tt[$key] = $translation;
		}
	}

	/**
	 * Remove tokens from the phrase so they are not automatically translated (and left alone by
	 * remote service)
	 *
	 * Return a structure which should be passed to postprocess_phrase to undo these changes
	 *
	 * @param string $phrase
	 * @return array
	 */
	private function preprocess_phrase(&$phrase) {
		$tokens = map_tokens($phrase);
		foreach ($tokens as $index => $token) {
			$map[$token] = '{' . $index . '}';
		}
		$phrase = tr($phrase, $map);
		return array_flip($map);
	}

	/**
	 * Replace mapped tokens with originals
	 *
	 * @param string $phrase
	 * @param array $map
	 * @return string
	 */
	private function postprocess_phrase($phrase, array $map) {
		return tr($phrase, $map);
	}
}
