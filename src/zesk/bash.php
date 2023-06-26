<?php
declare(strict_types=1);
namespace zesk;

use zesk\Interface\SettingsInterface;

/**
 * Utilities for bash-related emulation
 *
 * @author kent
 *
 */
class bash {
	/**
	 *
	 */
	private const UNQUOTE_PAIRS = '\'\'""';

	public static function substitute($value, SettingsInterface $settings, array &$dependencies = null, $lower_dependencies = false) {
		if (!is_array($dependencies)) {
			$dependencies = [];
		}
		if (is_array($value)) {
			foreach ($value as $k => $v) {
				$value[$k] = self::substitute($v, $settings, $dependencies, $lower_dependencies);
			}
			return $value;
		}
		$matches = [];
		// Handle ${FOO:-default} correctly
		if (preg_match_all('/\${([^}]+)}/', $value, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$variable = $match[1];
				$default_value = '';
				foreach ([
					':-',
					':=',
				] as $sep) {
					if (str_contains($variable, $sep)) {
						[$variable, $default_value] = explode($sep, $variable, 2);
						$default_value = StringTools::unquote($default_value, self::UNQUOTE_PAIRS);
						break;
					}
				}
				if ($lower_dependencies) {
					$variable = strtolower($variable);
				}
				$value = str_replace($match[0], $settings->get($variable, $default_value), $value);
				$dependencies[$variable] = true;
			}
		}
		if (!str_contains($value, '$')) {
			return $value;
		}
		foreach ([
			'/\$([A-Za-z0-9_]+)/',
			'/\$\{([^}]+)\}/',
		] as $pattern) {
			// Correctly handle $FOO values within a set value (like sh or bash would support)
			if (preg_match_all($pattern, $value, $matches, PREG_SET_ORDER)) {
				foreach ($matches as $match) {
					$variable = $match[1];
					if ($lower_dependencies) {
						$variable = strtolower($variable);
					}
					$value = str_replace($match[0], strval($settings->get($variable, '')), $value);
					$dependencies[$variable] = true;
				}
			}
		}
		return $value;
	}
}
