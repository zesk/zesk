<?php

namespace zesk;

/**
 * Utilities for bash-related emulation
 *
 * @author kent
 *
 */
class bash {

	public static function substitute($value, array $variables) {
		if (is_array($value)) {
			foreach ($value as $k => $v) {
				$value[$k] = self::substitute($v, $variables);
			}
			return $value;
		}
		$matches = false;
		// Handle ${FOO:-default} correctly
		if (preg_match_all('/\$\{([^}]+)\}/', $value, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$other_var = $default_value = null;
				list($other_var, $default_value) = pair($match[1], ':-', $match[1], false);
				if ($default_value !== false) {
					$value = str_replace($match[0], avalue($variables, strtolower($other_var), $default_value), $value);
				}
			}
		}
		if (strpos($value, '$') !== false) {
			// Correctly handle $FOO values within a set value (like sh or bash would support)
			if (preg_match_all('/\$([A-Za-z0-9_]+)/', $value, $matches, PREG_SET_ORDER)) {
				foreach ($matches as $match) {
					$value = str_replace($match[0], avalue($variables, strtolower($match[1]), ''), $value);
				}
			}
			if (preg_match_all('/\$\{([^}]+)\}/', $value, $matches, PREG_SET_ORDER)) {
				foreach ($matches as $match) {
					$value = str_replace($match[0], avalue($variables, strtolower($match[1]), ''), $value);
				}
			}
		}
		return $value;
	}
}