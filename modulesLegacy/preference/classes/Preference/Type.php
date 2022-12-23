<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage user
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

/**
 * @see Class_Preference_Type
 * @author kent
 *
 */
class Preference_Type extends ORMBase {
	/**
	 * Find a preference type with the given name
	 *
	 * @param string $code_name
	 * @param ?string $name
	 * @return Preference_Type
	 */
	public static function registerName(Application $application, string $code_name, string $name = null): self {
		$fields = [
			'name' => $name ?: $code_name,
			'code' => $code_name,
		];
		$pref = $application->ormFactory(__CLASS__, $fields);
		return $pref->register();
	}
}
