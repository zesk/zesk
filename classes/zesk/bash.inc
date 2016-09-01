<?php

namespace zesk;

/**
 * Utilities for bash-related emulation
 *
 * @author kent
 *
 */
class bash {
	public static function substitute($value, \Interface_Settings $settings, array &$dependencies = null) {
		if (!is_array($dependencies)) {
			$dependencies = array();
		}
		if (is_array($value)) {
			foreach ($value as $k => $v) {
				$value[$k] = self::substitute($v, $settings, $dependencies);
			}
			return $value;
		}
		$matches = false;
		// Handle ${FOO:-default} correctly
		$depends = array();
		if (preg_match_all('/\$\{([^}]+)\}/', $value, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$variable = strtolower($match[1]);
				$default_value = "";
				$found_sep = null;
				$found_quote = null;
				foreach (array(
					":-",
					":="
				) as $sep) {
					if (strpos($variable, $sep) !== false) {
						list($variable, $default_value) = explode($sep, $variable, 2);
						$variable = strtolower($variable);
						$default_value = unquote($default_value, '\'\'""', $found_quote);
						$found_sep = $sep;
						break;
					}
				}
				$value = str_replace($match[0], $settings->get($variable, $default_value), $value);
				$dependencies[$variable] = true;
			}
		}
		if (strpos($value, '$') !== false) {
			foreach (array(
				'/\$([A-Za-z0-9_]+)/',
				'/\$\{([^}]+)\}/'
			) as $pattern) {
				// Correctly handle $FOO values within a set value (like sh or bash would support)
				if (preg_match_all($pattern, $value, $matches, PREG_SET_ORDER)) {
					foreach ($matches as $match) {
						$variable = strtolower($match[1]);
						$value = str_replace($match[0], $settings->get($variable, ""), $value);
						if (!array_key_exists($variable, $dependencies)) {
							$dependencies[$variable] = true;
						}
					}
				}
			}
		}
		return $value;
	}
}