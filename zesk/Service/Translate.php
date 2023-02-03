<?php declare(strict_types=1);
namespace zesk;

abstract class Service_Translate extends Service {
	/**
	 * @param string $phrase
	 * @return string
	 */
	abstract public function translate(string $phrase): string;

	/**
	 *
	 * @param Application $application
	 * @param string $target_language
	 * @param string $source_language
	 * @param array $options
	 * @return Service_Translate
	 */
	public static function factory_translate(Application $application, $target_language, $source_language = null, array $options = []) {
		return self::factory($application, 'translate', $target_language, $source_language = null, $options);
	}
}
