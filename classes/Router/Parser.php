<?php
declare(strict_types=1);

namespace zesk\Router;

use zesk\JSON;
use zesk\Route;
use zesk\Router;
use zesk\StringTools;
use zesk\Exception_Parse;
use zesk\Exception_Syntax;

/**
 *
 * @author kent
 *
 */
class Parser {
	/**
	 *
	 * @var string
	 */
	protected string $contents;

	/**
	 *
	 * @var string
	 */
	protected string $id;

	/**
	 *
	 * @param string $contents
	 *
	 */

	/**
	 * Parser constructor
	 *
	 * @param string $contents
	 * @param string $id
	 */
	public function __construct(string $contents, string $id = '') {
		$this->contents = $contents;
		$this->id = $id ?: md5($contents);
	}

	/**
	 * Parser constructor
	 *
	 * @param string $contents
	 * @param string $id
	 * @return static
	 */
	public static function factory(string $contents, string $id = ''): self {
		return new self($contents, $id);
	}

	/**
	 *
	 * @param Router $router
	 * @param array $add_options
	 * @return Route[]
	 * @throws Exception_Syntax
	 */
	public function execute(Router $router, array $add_options = []): array {
		$app = $router->application;
		$logger = $app->logger;

		$lines = explode("\n", $this->contents);
		$paths = [];
		$options = [];
		$whites = toList(" ;\t");
		$tr = [
			'$zesk_root' => $app->zeskHome(), '$zesk_application_root' => $app->path(),
		];
		$routes = [];
		foreach ($lines as $lineno => $line) {
			$lineno1 = $lineno + 1; // 1-based line number
			$firstChar = substr($line, 0, 1);
			$line = trim($line);
			if (empty($line) || $line[0] === '#') {
				continue;
			}
			if (in_array($firstChar, $whites)) {
				if (count($paths) === 0) {
					$logger->warning("Line $lineno1 of router has setting without path");
				} elseif (!str_contains($line, '=')) {
					$logger->warning("Line $lineno1 of router has no value ($line)");
				} else {
					[$name, $value] = explode('=', $line, 2);
					$value_trimmed = trim($value);
					if ($value_trimmed === 'null') {
						$value = null;
					} elseif ($value_trimmed === 'true' || $value_trimmed === 'false') {
						$value = toBool($value_trimmed);
					} elseif (StringTools::begins($value_trimmed, str_split('"\'{[', 1))) {
						try {
							$decoded = JSON::decode($value, true);
							$value = $decoded;
						} catch (Exception_Parse $e) {
							$logger->error('Error parsing {id}:{lineno} decoding JSON failed', [
								'id' => $this->id, 'lineno' => $lineno1,
							]);
							$app->hooks->call('exception', $e);
						}
					}
					if (is_string($value) || is_array($value)) {
						$value = tr($value, $tr);
					}
					if (str_ends_with($name, '[]')) {
						$options[strtolower(substr($name, 0, -2))][] = $value;
					} else {
						$options[strtolower($name)] = $value;
					}
				}
			} else {
				// Transition to new tag
				if (count($options) === 0 || count($paths) === 0) {
					$paths[] = unquote($line);
				} else {
					if ($add_options) {
						$options += $add_options;
					}
					foreach ($paths as $path) {
						$routes[] = $router->addRoute($path, $options);
					}
					$options = [];
					$paths = [
						unquote($line),
					];
				}
			}
		}
		if (count($paths) > 0 && count($options) === 0) {
			throw new Exception_Syntax('Final router {paths} has no valid options', [
				'paths' => $paths,
			]);
		} else {
			foreach ($paths as $path) {
				$routes[] = $router->addRoute($path, $options);
			}
		}
		return $routes;
	}
}
