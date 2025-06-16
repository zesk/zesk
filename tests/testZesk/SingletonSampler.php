<?php
declare(strict_types=1);

namespace testZesk;

class SingletonSampler
{
	public static string $serialNo = '';

	public string $id;

	public function __construct(string $id)
	{
		$this->id = $id;
	}

	public static function singleton(): self
	{
		return new self(self::$serialNo);
	}
}
