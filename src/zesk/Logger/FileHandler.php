<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2023 Market Acumen, Inc.
 * @author kent
 * @category Management
 * @package zesk
 * @subpackage logger
 */
namespace zesk\Logger;

class FileHandler implements Handler
{
	/**
	 * File
	 * @var mixed $resource
	 */
	public mixed $resource;

	public function __construct(string $name)
	{
		$this->resource = fopen($name, 'ab');
	}

	public function log(string $message, array $context = []): void
	{
		$prefix = $context['_levelString'] ?? '';
		if ($prefix) {
			$prefix .= ': ';
		}
		$string = $prefix . map($message, $context);
		if (!str_ends_with($string, "\n")) {
			$string .= "\n";
		}
		fwrite($this->resource, $string);
		fflush($this->resource);
	}

	public function __destruct()
	{
		fclose($this->resource);
	}
}
