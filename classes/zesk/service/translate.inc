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

	static public function factory_translate($target_language, $source_language = null, array $options = array()) {
		return self::factory("translate", $target_language, $source_language = null, $options);
	}
}