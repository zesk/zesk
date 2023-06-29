<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk\Configuration\Parser;

use Throwable;
use zesk\ArrayTools;
use zesk\Configuration;
use zesk\Configuration\Parser;
use zesk\Directory;
use zesk\Exception\KeyNotFound;
use zesk\Exception\ParseException;
use zesk\File;
use zesk\JSON as JSONTools;
use zesk\Types;

/**
 *
 * @author kent
 *
 */
class JSON extends Parser
{
	protected array $parseOptions = [
		'overwrite' => true, 'lower' => false, 'interpolate' => true,
	];

	/**
	 *
	 */
	public function initialize(): void
	{
	}

	/**
	 * @return boolean
	 */
	public function validate(): bool
	{
		try {
			return is_array(JSONTools::decode($this->content));
		} catch (Throwable) {
			return false;
		}
	}

	/**
	 * @throws ParseException
	 */
	public function process(): void
	{
		$lower = $this->parseOptions['lower'] ?? false;
		$interpolate = $this->parseOptions['interpolate'] ?? false;

		$result = JSONTools::decode($this->content);

		if (!is_array($result)) {
			$message = '{method} JSON::decode returned non-array {type}';
			$__ = [
				'method' => __METHOD__, 'type' => Types::type($result),
			];
			error_log(ArrayTools::map($message, $__));

			throw new ParseException($message, $__);
		}
		if ($lower) {
			$result = array_change_key_case($result);
		}
		$include = null;
		if (array_key_exists('include', $result) && $this->loader) {
			$include = $result['include'];
			unset($result['include']);
		}
		$this->mergeResults($result, [], $interpolate);
		if ($include) {
			$this->handle_include($include, $this->option('context'));
		}
	}

	/**
	 * Handle include files specially
	 *
	 * @param string $file Name of additional include file
	 */
	private function handle_include(string $file, string $context = null): void
	{
		if (File::isAbsolute($file)) {
			$this->loader->appendFiles([
				$file,
			]);
		} elseif ($context && is_dir($context) && File::pathCheck($file)) {
			$full = Directory::path($context, $file);
			$this->loader->appendFiles([$full]);
		} else {
			error_log(ArrayTools::map('{method} {file} context {context} was a no-op', [
				'method' => __METHOD__, 'file' => $file, 'context' => $context,
			]));
		}
	}

	/**
	 *
	 * @param array $results
	 * @param array $path
	 * @param bool $interpolate
	 * @return void
	 */
	private function mergeResults(array $results, array $path = [], bool $interpolate = false): void
	{
		$dependency = $this->dependency;
		$settings = $this->settings;
		foreach ($results as $key => $value) {
			$matches = null;
			$current_path = array_merge($path, [
				$key,
			]);
			if (is_array($value)) {
				$this->mergeResults($value, $current_path, $interpolate);
			} elseif (is_string($value) && $interpolate && preg_match_all('/\$\{([^}]+)}/', $value, $matches, PREG_SET_ORDER)) {
				$dependencies = [];
				$map = [];
				foreach ($matches as $match) {
					[$token, $variable] = $match;

					try {
						$map[$token] = strval($settings->get($variable));
					} catch (KeyNotFound) {
						$map[$token] = '';
					}
					$dependencies[$variable] = true;
				}
				$value = strtr($value, $map);
				$variable = implode(Configuration::key_separator, $current_path);
				$settings->set($variable, $value);
				$dependency?->defines($variable, array_keys($dependencies));
			} else {
				$variable = implode(Configuration::key_separator, $current_path);
				$settings->set($variable, $value);
				$dependency?->defines($variable);
			}
		}
	}
}
