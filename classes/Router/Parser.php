<?php
namespace zesk\Router;

use zesk\JSON;
use zesk\Router;
use zesk\StringTools;
use zesk\Exception_Parse;

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
	protected $contents = null;

	/**
	 *
	 * @var string
	 */
	protected $id = null;

	/**
	 *
	 * @param unknown $contents
	 */
	public function __construct($contents, $id = null) {
		$this->contents = $contents;
		$this->id = $id ? $id : md5($contents);
	}

	/**
	 *
	 * @param Router $router
	 */
	public function execute(Router $router, array $add_options = null) {
		$app = $router->application;
		$logger = $app->logger;

		$lines = explode("\n", $this->contents);
		$paths = array();
		$options = array();
		$whites = to_list(" ;\t");
		$tr = array(
			'$zesk_root' => $app->zesk_home(),
			'$zesk_application_root' => $app->path(),
		);
		$routes = array();
		foreach ($lines as $lineno => $line) {
			$lineno1 = $lineno + 1; // 1-based line number
			$firstc = substr($line, 0, 1);
			$line = trim($line);
			if (empty($line) || $line[0] === '#') {
				continue;
			}
			if (in_array($firstc, $whites)) {
				if (count($paths) === 0) {
					$logger->warning("Line $lineno1 of router has setting without path");
				} else {
					list($name, $value) = pair($line, "=", $line, null);
					if ($value === null) {
						$logger->warning("Line $lineno1 of router has no value ($line)");
					} else {
						$trimvalue = trim($value);
						if ($trimvalue === "null") {
							$value = null;
						} elseif ($trimvalue === "true" || $trimvalue === "false") {
							$value = to_bool($trimvalue);
						} elseif (StringTools::begins($trimvalue, str_split("\"'{[", 1))) {
							try {
								$decoded = JSON::decode($value, true);
								$value = $decoded;
							} catch (Exception_Parse $e) {
								$logger->error("Error parsing {id}:{lineno} decoding JSON failed", array(
									"id" => $this->id,
									"lineno" => $lineno1,
								));
								$app->hooks->call("exception", $e);
							}
						}
						if (is_string($value) || is_array($value)) {
							$value = tr($value, $tr);
						}
						if (ends($name, "[]")) {
							$options[strtolower(substr($name, 0, -2))][] = $value;
						} else {
							$options[strtolower($name)] = $value;
						}
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
						$routes[] = $router->add_route($path, $options);
					}
					$options = array();
					$paths = array(
						unquote($line),
					);
				}
			}
		}
		if (count($paths) > 0 && count($options) === 0) {
			$logger->error("Router {path} has no valid options {options_string}", array(
				"path" => $path,
				"options" => $options,
				"options_string" => JSON::encode($options),
			));
		} else {
			foreach ($paths as $path) {
				$routes[] = $router->add_route($path, $options);
			}
		}
		return $routes;
	}
}
