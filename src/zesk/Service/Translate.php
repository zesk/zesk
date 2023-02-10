<?php
declare(strict_types=1);

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
	/**
	 * @param Application $application
	 * @param string $target_language
	 * @param string|null $source_language
	 * @param array $options
	 * @return self
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Semantics
	 */
	public static function translateFactory(Application $application, string $target_language, string $source_language =
	null, array                                         $options = []) {
		$result = self::factory($application, 'translate', [
			'target' => $target_language,
			'source' => $source_language,
		] + $options);
		assert($result instanceof self);
		return $result;
	}
}
