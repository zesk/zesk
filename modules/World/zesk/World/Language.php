<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 * Created on Tue Jul 15 12:37:21 EDT 2008
 */

namespace zesk\World;

use Throwable;
use zesk\Application;
use zesk\Exception\KeyNotFound;
use zesk\Exception\Semantics;
use zesk\Locale\Locale;
use zesk\ORM\ORMBase;

/**
 *
 * @see Class_Language
 * @author kent
 * @property int $id
 * @property string $code
 * @property string $dialect
 * @property string $name
 */
class Language extends ORMBase {
	public const MEMBER_ID = 'id';

	public const MEMBER_CODE = 'code';

	public const MEMBER_DIALECT = 'dialect';

	public const MEMBER_NAME = 'name';

	public function locale_string(): string {
		if ($this->memberIsEmpty(self::MEMBER_DIALECT)) {
			return strtolower($this->code);
		}
		return strtolower($this->code) . '_' . strtoupper($this->dialect);
	}

	public static function lang_name(Application $application, $code, Locale $locale = null): string {
		[$language, $dialect] = pair($code, '_', $code);
		if (empty($dialect)) {
			$dialect = null;
		}

		try {
			$lang_en = $application->ormRegistry(__CLASS__)->querySelect()->addWhat('name', self::MEMBER_NAME)->appendWhere([
				self::MEMBER_CODE => $language, self::MEMBER_DIALECT => $dialect,
			])->one('name');
			if (!$locale) {
				$locale = $application->locale;
			}
			return $locale->__("Locale:=$lang_en");
		} catch (Throwable) {
		}
		return "[$code]";
	}

	/**
	 *
	 * @param Application $application
	 * @throws Database\Exception\Duplicate
	 * @throws Database\Exception\NoResults
	 * @throws Database\Exception\TableNotFound
	 * @throws KeyNotFound
	 * @throws Semantics
	 */
	public static function clean_table(Application $application): void {
		$query = $application->ormRegistry(__CLASS__)->queryUpdate();
		$query->setValues(['dialect' => null])->appendWhere(['dialect' => '']);
		$query->execute();
		if ($query->affectedRows() > 0) {
			$application->logger->warning('{method} updated {n} non-NULL rows', [
				'method' => __METHOD__, 'n' => $query->affectedRows(),
			]);
		}
	}
}
