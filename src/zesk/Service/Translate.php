<?php
declare(strict_types=1);

namespace zesk\Service;

use zesk\Service;
use zesk\Application;

use zesk\Exception\ClassNotFound;

abstract class Translate extends Service {
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
	 * @return Translate
	 */
	/**
	 * @param Application $application
	 * @param string $target_language
	 * @param string|null $source_language
	 * @param array $options
	 * @return self
	 * @throws ClassNotFound
	 */
	public static function translateFactory(Application $application, string $target_language, string $source_language =
	null, array                                         $options = []): self {
		$result = self::factory($application, 'translate', [
			'target' => $target_language,
			'source' => $source_language,
		] + $options);
		assert($result instanceof self);
		return $result;
	}
}
