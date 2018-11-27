<?php
namespace zesk;

abstract class Service_Translate extends Service {
	/**
	 *
	 * @param string $source_language
	 * @param string $target_language
	 * @return Service_Translate
	 */
	abstract public function translate($phrase);

	/**
	 *
	 * @param Application $application
	 * @param string $target_language
	 * @param string $source_language
	 * @param array $options
	 * @return Service_Translate
	 */
	public static function factory_translate(Application $application, $target_language, $source_language = null, array $options = array()) {
		return self::factory($application, "translate", $target_language, $source_language = null, $options);
	}
}
