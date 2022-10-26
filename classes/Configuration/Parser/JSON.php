<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk;

/**
 *
 * @author kent
 *
 */
class Configuration_Parser_JSON extends Configuration_Parser {
	protected array $options = [
		'overwrite' => true,
		'lower' => true,
		'interpolate' => true,
	];

	/**
	 *
	 */
	public function initialize(): void {
	}

	/**
	 * @return boolean
	 */
	public function validate(): bool {
		try {
			return is_array(JSON::decode($this->content));
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 *
	 */
	public function process(): void {
		$lower = $this->options['lower'] ?? false;
		$interpolate = $this->options['interpolate'] ?? false;

		$result = JSON::decode($this->content);

		if (!is_array($result)) {
			$message = '{method} JSON::decode returned non-array {type}';
			$__ = [
				'method' => __METHOD__,
				'type' => type($result),
			];
			error_log(map($message, $__));

			throw new Exception_File_Format($message, $__);
		}
		if ($lower) {
			$result = array_change_key_case($result);
		}
		$include = null;
		if (array_key_exists('include', $result) && $this->loader) {
			$include = $result['include'];
			unset($result['include']);
		}
		$this->merge_results($result, [], $interpolate);
		if ($include) {
			$this->handle_include($include, $this->option('context'));
		}
	}

	/**
	 * Handle include files specially
	 *
	 * @param string $file Name of additional include file
	 */
	private function handle_include(string $file, string $context = null): void {
		if (File::isAbsolute($file)) {
			$this->loader->appendFiles([
				$file,
			]);
		} elseif ($context && is_dir($context) && File::path_check($file)) {
			$full = path($context, $file);
			$this->loader->appendFiles([$full]);
		} else {
			error_log(map('{method} {file} context {context} was a no-op', [
				'method' => __METHOD__,
				'file' => $file,
				'context' => $context,
			]));
		}
	}

	/**
	 *
	 * @param array $results
	 * @param array $path
	 * @param boolean $interpolate
	 */
	private function merge_results(array $results, array $path = [], bool $interpolate = false): void {
		$dependency = $this->dependency;
		$settings = $this->settings;
		foreach ($results as $key => $value) {
			$matches = null;
			$current_path = array_merge($path, [
				$key,
			]);
			if (is_array($value)) {
				$this->merge_results($value, $current_path, $interpolate);
			} elseif (is_string($value) && $interpolate && preg_match_all('/\$\{([^\}]+)\}/', $value, $matches, PREG_SET_ORDER)) {
				$dependencies = [];
				foreach ($matches as $match) {
					[$token, $variable] = $match;
					$map[$token] = strval($settings->get($variable));
					$dependencies[$variable] = true;
				}
				$value = strtr($value, $map);
				$variable = implode(Configuration::key_separator, $current_path);
				$settings->set($variable, $value);
				if ($dependency) {
					$dependency->defines($variable, array_keys($dependencies));
				}
			} else {
				$variable = implode(Configuration::key_separator, $current_path);
				$settings->set($variable, $value);
				if ($dependency) {
					$dependency->defines($variable);
				}
			}
		}
	}
}
