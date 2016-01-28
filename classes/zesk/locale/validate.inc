<?php

namespace zesk;

use \str as str;
use \log as log;
use \json as json;

class Locale_Validate extends \Options {

	public function __construct($options = null) {
		parent::__construct($options);
		$this->inherit_global_options();
	}

	/**
	 * Verifies if the source variables exist in the translation
	 *
	 * Uses global Locale::group_check_methods which is an array of group prefixes for special checking.
	 *
	 * e.g.
	 *
	 * Locale::group_check_methods={"Timestamp": "braces"}
	 *
	 * Values should be an array of semicolon list of one of
	 *
	 * - token_count - checks that the number of tokens matches between source and translation
	 * - token_names - checks that all source tokens exist in translation
	 * - braces - checks that braces match in order in source and translation
	 *
	 * @param string $source
	 * @param string $target
	 * @return array
	 */
	public function check_translation($source, $translation) {
		list($group, $phrase) = pair($source, ":=", null, $source);
		$methods = to_list("token_names;braces");
		if ($group !== null) {
			$check_methods = array_change_key_case($this->option_array("group_check_methods", array()));
			foreach ($check_methods as $check_method => $methods_list) {
				if (beginsi($group, $check_method)) {
					$methods = $methods_list;
					break;
				}
			}
		}
		$errors = array();
		foreach ($methods as $method) {
			$full_method = "check_translation_$method";
			if (method_exists($this, $full_method)) {
				$errors = array_merge($errors, $this->$full_method($source, $translation));
			} else {
				log::error("Invalid translation check method: {method} for group {group}", compact("method", "group"));
			}
		}
		return $errors;
	}

	private function extract_tokens($string) {
		$matches = array();
		preg_match_all('/\{[^}]+\}/', $string, $matches, PREG_PATTERN_ORDER);
		$matches = $matches[0];
		sort($matches);
		return $matches;
	}
	private function extract_braces($string) {
		$matches = array();
		preg_match_all('/[\[\]]/', $string, $matches, PREG_PATTERN_ORDER);
		$matches = $matches[0];
		return $matches;
	}

	public function check_translation_token_count($source, $translation) {
		$source = str::right($source, ":=", $source);
		$source_matches = $this->extract_tokens($source);
		$translation_matches = $this->extract_tokens($translation);
		$errors = array();
		$n = count($source_matches) - count($translation_matches);
		if ($n > 0) {
			$errors[] = __("Missing {n_tokens} in translation", array(
				"n_tokens" => Locale::plural_word("token", $n)
			));
		} else if ($n < 0) {
			$errors[] = __("You have an additional {n_tokens} in your translation", array(
				"n_tokens" => Locale::plural_word("token", -$n)
			));
		}
		return $errors;
	}

	public function check_translation_token_names($source, $translation) {
		$source = str::right($source, ":=", $source);
		$source_matches = $this->extract_tokens($source);
		$translation_matches = $this->extract_tokens($translation);
		$errors = array();
		if ($translation_matches !== $source_matches) {
			$missing = array_diff($source_matches, $translation_matches);
			if (count($missing) > 0) {
				$errors[] = __("Target phrase is missing the following variables: {missing}", array(
					"missing" => implode(", ", $missing)
				));
			}
			$extras = array_diff($translation_matches, $source_matches);
			if (count($extras) > 0) {
				$errors[] = __("Target phrase has extra variables it shouldn't have: {extras}", array(
					"extras" => implode(", ", $extras)
				));
			}
		}
		return $errors;
	}

	public function check_translation_braces($source, $translation) {
		$source = str::right($source, ":=", $source);
		$source_matches = $this->extract_braces($source);
		$translation_matches = $this->extract_braces($translation);
		$stack = 0;
		$errors = array();
		foreach ($source_matches as $index => $bracket) {
			if ($bracket === ']' && $stack === 0) {
				$errors[] = __("Unexpected close brace &quot;]&quot; found before open brace; are the braces balanced?");
				return $errors;
			}
			$stack += ($bracket === ']') ? 1 : -1;
			if (!array_key_exists($index, $translation_matches)) {
				$errors[] = __("Translation is missing a pair of braces.");
				return $errors;
			}
			$translation_bracket = $translation_matches[$index];
			if ($translation_bracket !== $bracket) {
				$errors[] = __("The {nth} brace in the translation ({translation_bracket}) does not match the source string bracket ({bracket})", array(
					"nth" => Locale::ordinal($index + 1),
					"debug" => json::encode($source_matches) . " " . json::encode($translation_matches)
				) + compact("translation_bracket", "bracket"));
				return $errors;
			}
		}
		if (count($translation_matches) > count($source_matches)) {
			$errors[] = __("The translation has an extra {num_braces} than the source phrase.", array(
				"n" => $n = count($translation_matches) - count($source_matches),
				"num_braces" => Locale::plural_word("brace", $n)
			));
		}
		return $errors;
	}
}