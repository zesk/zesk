<?php declare(strict_types=1);
/**
 *
 */

use zesk\Application;
use zesk\Exception_Configuration;
use zesk\Exception_Semantics;
use zesk\Hookable;
use zesk\JSON;
use zesk\StringTools;

/**
 *
 * @author kent
 *
 */
class Validate extends Hookable {
	/**
	 *
	 * @param Application $application
	 * @param array $options
	 */
	public function __construct(Application $application, array $options = []) {
		parent::__construct($application, $options);
		$this->inheritConfiguration();
	}

	/**
	 * @param array $methodNames
	 * @return array
	 * @throws Exception_Configuration
	 */
	private function checkMethodsToClosures(array $methodNames): array {
		$methods = [
			'tokenNames' => $this->check_translation_token_names(...),
			'braces' => $this->check_translation_braces(...),
			'tokenCount' => $this->check_translation_token_count(...),
		];
		$results = [];
		foreach ($methodNames as $methodName) {
			if (!is_string($methodName) || !array_key_exists($methodName, $methods)) {
				throw new Exception_Configuration(self::class . '::groupCheckMethods', 'Need strings {keys}', ['keys' => array_keys($methods)]);
			}
		}
		return $results;
	}

	/**
	 * Verifies if the source variables exist in the translation
	 *
	 * Uses global zesk\Locale\Validate::group_check_methods which is an array of group prefixes for special checking.
	 *
	 * e.g.
	 *
	 * zesk\Locale::group_check_methods={"Timestamp": "braces"}
	 *
	 * Values should be an array of semicolon list of one of
	 *
	 * - token_count - checks that the number of tokens matches between source and translation
	 * - token_names - checks that all source tokens exist in translation
	 * - braces - checks that braces match in order in source and translation
	 *
	 * @param string $source
	 * @param string $translation
	 * @return array
	 * @throws Exception_Configuration
	 */
	public function check_translation(string $source, string $translation): array {
		[$group] = pair($source, ':=', '', $source);
		$methods = $this->checkMethodsToClosures(['tokenNames', 'tokenCount', 'braces']);
		if ($group !== '') {
			$check_methods = array_change_key_case($this->optionArray('group_check_methods'));
			foreach ($check_methods as $check_method => $methods_list) {
				if (str_starts_with($group, $check_method)) {
					$methods = $this->checkMethodsToClosures($methods_list);
					break;
				}
			}
		}
		$errors = [];
		foreach ($methods as $method) {
			$errors = array_merge($errors, $method($source, $translation));
		}
		return $errors;
	}

	/**
	 *
	 * @param string $string
	 * @return string[]
	 */
	private function extract_tokens(string $string): array {
		$matches = [];
		preg_match_all('/\{[^}]+}/', $string, $matches, PREG_PATTERN_ORDER);
		$matches = $matches[0];
		sort($matches);
		return $matches;
	}

	/**
	 * Match braces in a string and return all matches
	 *
	 * @param string $string
	 * @return array
	 */
	private function extract_braces(string $string): array {
		$matches = [];
		preg_match_all('/[\[\]]/', $string, $matches, PREG_PATTERN_ORDER);
		return $matches[0];
	}

	/**
	 * Check the token count in the source and translation are the same
	 *
	 * @param string $source
	 * @param string $translation
	 * @return string[] An array of errors found in the two strings when compared
	 */
	public function check_translation_token_count(string $source, string $translation): array {
		$source = StringTools::right($source, ':=', $source);
		$source_matches = $this->extract_tokens($source);
		$translation_matches = $this->extract_tokens($translation);
		$errors = [];
		$n = count($source_matches) - count($translation_matches);
		$locale = $this->application->locale;
		if ($n > 0) {
			$errors[] = $locale->__('Missing {n_tokens} in translation', [
				'n_tokens' => $locale->plural_word('token', $n),
			]);
		} elseif ($n < 0) {
			$errors[] = $locale->__('You have an additional {n_tokens} in your translation', [
				'n_tokens' => $locale->plural_word('token', -$n),
			]);
		}
		return $errors;
	}

	/**
	 * Check that the token names match between source and translation
	 *
	 * @param string $source
	 * @param string $translation
	 * @return string[] An array of errors found in the two strings when compared
	 */
	public function check_translation_token_names(string $source, string $translation): array {
		$source = StringTools::right($source, ':=', $source);
		$source_matches = $this->extract_tokens($source);
		$translation_matches = $this->extract_tokens($translation);
		$errors = [];
		if ($translation_matches !== $source_matches) {
			$locale = $this->application->locale;
			$missing = array_diff($source_matches, $translation_matches);
			if (count($missing) > 0) {
				$errors[] = $locale->__('Target phrase is missing the following variables: {missing}', [
					'missing' => implode(', ', $missing),
				]);
			}
			$extras = array_diff($translation_matches, $source_matches);
			if (count($extras) > 0) {
				$errors[] = $locale->__('Target phrase has extra variables it shouldn\'t have: {extras}', [
					'extras' => implode(', ', $extras),
				]);
			}
		}
		return $errors;
	}

	/**
	 * Check that the braces in the pattern ar balanced and match between source and translation
	 *
	 * @param string $source
	 * @param string $translation
	 * @return string[] An array of errors found when the two strings are compared
	 * @throws Exception_Semantics
	 */
	public function check_translation_braces(string $source, string $translation): array {
		$source = StringTools::right($source, ':=', $source);
		$source_matches = $this->extract_braces($source);
		$translation_matches = $this->extract_braces($translation);
		$stack = 0;
		$errors = [];
		$locale = $this->application->locale;
		foreach ($source_matches as $index => $bracket) {
			if ($bracket === ']' && $stack === 0) {
				$errors[] = $locale->__('Unexpected close brace &quot;]&quot; found before open brace; are the braces balanced?');
				return $errors;
			}
			$stack += ($bracket === ']') ? 1 : -1;
			if (!array_key_exists($index, $translation_matches)) {
				$errors[] = $locale->__('Translation is missing a pair of braces.');
				return $errors;
			}
			$translation_bracket = $translation_matches[$index];
			if ($translation_bracket !== $bracket) {
				$errors[] = $locale->__('The {nth} brace in the translation ({translation_bracket}) does not match the source string bracket ({bracket})', [
					'nth' => $locale->ordinal($index + 1),
					'debug' => JSON::encode($source_matches) . ' ' . JSON::encode($translation_matches),
				] + compact('translation_bracket', 'bracket'));
				return $errors;
			}
		}
		if (count($translation_matches) > count($source_matches)) {
			$errors[] = $locale->__('The translation has an extra {num_braces} than the source phrase.', [
				'n' => $n = count($translation_matches) - count($source_matches),
				'num_braces' => $locale->plural_word('brace', $n),
			]);
		}
		return $errors;
	}
}
