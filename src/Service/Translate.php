<?php
declare(strict_types=1);

namespace zesk\Service;

use zesk\Service;
use zesk\Application;

use zesk\Exception\ClassNotFound;

abstract class Translate extends Service
{
	/**
	 * @param string $phrase
	 * @return string
	 */
	abstract public function translate(string $phrase): string;

	/**
	 * @param Application $application
	 * @param string      $targetLanguage
	 * @param string|null $sourceLanguage
	 * @param array       $options
	 * @return self
	 * @throws ClassNotFound
	 */
	public static function translateFactory(Application $application, string $targetLanguage, string $sourceLanguage = null, array $options = []): self
	{
		$result = self::factory($application, 'translate', [
			'target' => $targetLanguage,
			'source' => $sourceLanguage,
		] + $options);
		assert($result instanceof self);
		return $result;
	}
}
