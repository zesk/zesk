<?php declare(strict_types=1);

/**
 * @package zesk
 * @subpackage contact
 */
namespace zesk;

/**
 *
 * @see Class_Contact_Label
 */
class Contact_Label extends ORMBase {
	public const LabelType_Address = 1;

	public const LabelType_Company = 2;

	public const LabelType_Date = 3;

	public const LabelType_Email = 4;

	public const LabelType_Phone = 5;

	public const LabelType_URL = 6;

	public const LabelType_XXX = 7;

	public const LabelType_Other = 8;

	public static function bootstrap() {
		return Contact_Label_Bootstrap::bootstrap();
	}

	public static function find_global(Application $application, $type, $name) {
		$fields = [
			'Account' => null,
			'CodeName' => $name,
			'Type' => $type,
		];
		return $application->ormRegistry(__CLASS__)
			->querySelect()
			->addWhat('ID', 'ID')
			->appendWhere($fields)
			->one('ID', null);
	}

	/**
	 *
	 * @param unknown $type
	 * @param unknown $name
	 * @param Account $account
	 * @return \zesk\unknown
	 */
	public static function register_local(ORMBase $account, $type, $name) {
		$app = $account->application;
		$fields = [
			'Account' => $account,
			'CodeName' => strtolower($name),
			'Type' => $type,
		];

		$id = $app->ormRegistry(__CLASS__)
			->querySelect()
			->what('ID', 'ID')
			->where($fields)
			->one('ID', null);
		if ($id) {
			return $id;
		}
		$fields = [
			'Account' => $account,
			'CodeName' => strtolower($name),
			'Name' => $name,
			'Type' => $type,
		];
		return $app->ormRegistry(__CLASS__)
			->queryUpdate()
			->values($fields)
			->execute();
	}

	/**
	 *
	 * @param unknown $type
	 * @param unknown $account
	 * @return \zesk\Ambigous
	 */
	public static function label_options(Application $application, $type, $account = null) {
		$account_where = null;
		if (!empty($account)) {
			$account_where = [
				$account,
				null,
			];
		}
		return $application->ormRegistry(__CLASS__)
			->querySelect()
			->addWhatIterable([
				'id' => 'ID',
				'name' => 'Name',
			])
			->appendWhere([
				'Type' => $type,
				'Account' => $account_where,
			])
			->toArray('id', 'name');
	}

	/**
	 *
	 * @param unknown $locale
	 * @return string
	 */
	public static function type_names($locale = null) {
		return __([
			self::LabelType_Address => 'Address',
			self::LabelType_Company => 'Company',
			self::LabelType_Date => 'Date',
			self::LabelType_Email => 'Email',
			self::LabelType_Phone => 'Phone',
			self::LabelType_URL => 'Website',
			self::LabelType_Other => 'Other',
		], $locale);
	}

	/**
	 *
	 * @return string
	 */
	public function type_name() {
		return $this->Name . ' ' . $this->type_names()[$this->Type] ?? 'Unknown Type';
	}

	/**
	 *
	 * @param unknown $types
	 * @param unknown $account
	 * @return \zesk\Ambigous
	 */
	public static function label_type_options(Application $app, $types = null, $account = null) {
		$table = $app->ormRegistry(__CLASS__)->table();
		$account_where = null;
		if (!empty($account)) {
			$account_where = [
				$account,
				null,
			];
		}
		$where = [
			'Account' => $account_where,
		];
		if ($types !== null) {
			$where['Type'] = $types;
		}
		$rows = $app->ormRegistry(__CLASS__)
			->querySelect()
			->addWhatIterable([
				'id' => 'ID',
				'name' => 'Name',
				'type' => 'Type',
			])
			->appendWhere($where)
			->toArray('id');

		$result = [];
		$type_names = self::type_names();
		foreach ($rows as $id => $row) {
			$name = $row['name'];
			$type_name = $type_names[$row['type']] ?? 'Unknown-' . $row['type'];
			$result[$type_name][$id] = $name;
		}
		return $result;
	}
}
