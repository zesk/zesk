<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage core
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */


namespace zesk;

use Stringable;
use zesk\Exception\ParseException;
use zesk\Exception\Redirect;

/**
 *
 * @author kent
 *
 */
class Model extends Hookable
{
	public const OPTION_DEFAULT_THEME = 'default_theme';

	/**
	 *
	 */
	public const DEFAULT_OPTION_DEFAULT_THEME = 'view';

	/**
	 * Option for theme path prefix for themes associated with this model
	 *
	 * @var string
	 */
	public const OPTION_THEME_PATH_PREFIX = 'themePathPrefix';

	/**
	 * @param array $value
	 * @return $this
	 */
	public function initializeFromArray(array $value): self
	{
		foreach ($value as $member => $memberValue) {
			if (property_exists($this, $member)) {
				$this->$member = $value;
			}
		}
		return $this;
	}

	/**
	 * Given a theme name, return the theme paths which are checked.
	 *
	 * Uses the class name and extrapolates within the theme search path, so:
	 *
	 * User_Role and $theme_name = "view" then searches for
	 *
	 * <code>
	 * user/role/view.tpl
	 * </code>
	 *
	 * In the theme path. If the theme_name begins with a slash or a period, no conversion is done.
	 *
	 * @param string|array $theme_names
	 * @return array
	 */
	public function themePaths(string|array $theme_names = ''): array
	{
		if ($theme_names === '') {
			$theme_names = [
				$this->option(self::OPTION_DEFAULT_THEME, self::DEFAULT_OPTION_DEFAULT_THEME),
			];
		} elseif (is_string($theme_names)) {
			if ($theme_names[0] === '/' || $theme_names[0] === '.') {
				return [
					$theme_names,
				];
			}
			$theme_names = [
				$theme_names,
			];
		}
		$result = [];
		foreach ($this->application->classes->hierarchy(get_class($this), __CLASS__) as $class) {
			$result = array_merge($result, ArrayTools::prefixValues($theme_names, $class . '/'));
		}
		if ($this->hasOption(self::OPTION_THEME_PATH_PREFIX)) {
			$result_prefix = ArrayTools::prefixValues($result, rtrim($this->option(self::OPTION_THEME_PATH_PREFIX), '/') . '/');
			$result = array_merge($result_prefix, $result);
		}
		return array_map(function ($name) {
			return strtr($name, [
				'_' => '/', '\\' => '/',
			]);
		}, $result);
	}

	/**
	 * Output this
	 *
	 * @param array|string $theme_names
	 *            Theme or list of themes to invoke (first found is used)
	 * @param array|string $variables
	 *            Variables to be passed to the template.
	 * @param string $default
	 *            Default value if no theme is found
	 * @return ?string
	 * @throws Redirect
	 */
	public function theme(array|string $theme_names = '', string|array $variables = [], string $default = ''): ?string
	{
		$variables = is_string($variables) ? [
			'content' => $variables,
		] : $variables;
		$variables += [
			'object' => $this, strtolower(get_class($this)) => $this,
		];
		return $this->application->themes->theme($this->themePaths($theme_names), $variables, [
			'default' => $default, 'first' => true,
		]);
	}

	/**
	 * Convert a variable to an ID. IDs may be arrays.
	 *
	 * @param $mixed mixed
	 * @return int|string|array
	 * @throws ParseException
	 */
	public static function mixedToID(mixed $mixed): int|string|array
	{
		if (is_object($mixed)) {
			if (method_exists($mixed, 'id')) {
				return $mixed->id();
			}
			if ($mixed instanceof Stringable) {
				return strval($mixed);
			}
		}
		if (is_numeric($mixed)) {
			return Types::toInteger($mixed);
		}
		if (is_string($mixed)) {
			return $mixed;
		}

		throw new ParseException('Unable to convert {mixed} ({type}) to ID', [
			'mixed' => $mixed, 'type' => Types::type($mixed),
		]);
	}
}
