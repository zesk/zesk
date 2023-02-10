<?php
declare(strict_types=1);

/**
 *
 */

namespace zesk\Polyglot\Command;

use zesk\ArrayTools;
use zesk\Command_Base;
use zesk\Exception;
use zesk\Locale;
use zesk\Polyglot\Module;
use zesk\Service;
use zesk\Service_Translate;

/**
 *
 * @author kent
 *
 */
class Translate extends Command_Base {
	/**
	 *
	 * @var integer
	 */
	public const ERROR_ARGS = 1;

	/**
	 *
	 * @var integer
	 */
	public const ERROR_FILE_FORMAT = 3;

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
	];

	/**
	 *
	 * @var array
	 */
	public array $option_help = [
		'source' => 'Source ', 'target' => 'string', 'interactive' => 'boolean', 'list' => 'boolean',
	];

	/**
	 *
	 * @var Service_Translate
	 */
	protected Service_Translate $service_object;

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Command::run()
	 */
	protected function run(): int {
		$app = $this->application;
		$source_language_file = $this->option('language-file', $app->configuration->getPath([
			Module::class, 'sourceFile',
		]));
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
			echo ArrayTools::joinSuffix($classes, "\n");
			return 0;
		}
		$target_language = $this->option('target');
		if (!$target_language) {
			$this->error('Please supply a --target language');
			return self::ERROR_ARGS;
		}
		$source_language = $this->option('source', $app->locale->language());

		$target_language = strtolower($target_language);
		$source_language = strtolower($source_language);

		$default_class = first($classes);
		/* @var $service_object Service_Translate */
		try {
			$service_object = $this->service_object = Service_Translate::translateFactory($app, $target_language, $source_language);
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
			return self::ERROR_FILE_FORMAT;
		}
		$translation_file = $app->load($source_language_file);
		if (!is_array($translation_file)) {
			$this->error('translation file {translation_file} does not return a translation table', compact('translation_file'));
			return self::ERROR_FILE_FORMAT;
		}
		if (count($translation_file) === 0) {
			$this->error('translation file {translation_file} is empty', compact('translation_file'));
			return self::ERROR_FILE_FORMAT;
		}

		$tt = [];
		foreach ($translation_file as $key => $phrase) {
			[$phrase, $context] = $this->preprocessPhrase($phrase);
			$translation = $service_object->translate($phrase);
			$translation = $this->postprocessPhrase($translation, $context);

			$tt[$key] = $translation;
		}
		if ($this->optionBool('verbose')) {
			$this->renderFormat($tt, 'text');
		}
		return 0;
	}

	/**
	 * Remove tokens from the phrase so they are not automatically translated (and left alone by
	 * remote service)
	 *
	 * Return a structure which should be passed to postprocess_phrase to undo these changes
	 *
	 * @param string $phrase
	 * @return array 2-item array with [$phrase, $context]
	 */
	private function preprocessPhrase(string $phrase): array {
		$tokens = mapExtractTokens($phrase);
		$map = [];
		foreach ($tokens as $index => $token) {
			$map[$token] = '{' . $index . '}';
		}
		$phrase = tr($phrase, $map);
		return [$phrase, array_flip($map)];
	}

	/**
	 * Replace mapped tokens with originals
	 *
	 * @param string $phrase
	 * @param array $map
	 * @return string
	 */
	private function postprocessPhrase(string $phrase, array $map): string {
		return tr($phrase, $map);
	}
}
