<?php

/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/contact/classes/contact/label.inc $
 * @package zesk
 * @subpackage contact
 */
namespace zesk;

/**
 *
 * @see Class_Contact_Label
 */
class Contact_Label extends Object {
	const LabelType_Address = 1;
	const LabelType_Company = 2;
	const LabelType_Date = 3;
	const LabelType_Email = 4;
	const LabelType_Phone = 5;
	const LabelType_URL = 6;
	const LabelType_XXX = 7;
	const LabelType_Other = 8;
	public static function bootstrap() {
		return Contact_Label_Bootstrap::bootstrap();
	}
	public static function find_global(Application $application, $type, $name) {
		$fields = array(
			'Account' => null,
			'CodeName' => $name,
			"Type" => $type
		);
		return $application->query_select(__CLASS__)->what("ID", "ID")->where($fields)->one("ID", null);
	}

	/**
	 *
	 * @param unknown $type
	 * @param unknown $name
	 * @param Account $account
	 * @return \zesk\unknown
	 */
	public static function register_local(Account $account, $type, $name) {
		$app = $account->application;
		$fields = array(
			'Account' => $account,
			'CodeName' => strtolower($name),
			"Type" => $type
		);

		$id = $app->query_select(__CLASS__)->what("ID", "ID")->where($fields)->one("ID", null);
		if ($id) {
			return $id;
		}
		$fields = array(
			'Account' => $account,
			'CodeName' => strtolower($name),
			"Name" => $name,
			'Type' => $type
		);
		return $app->query_update(__CLASS__)->values($fields)->execute();
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
			$account_where = array(
				$account,
				null
			);
		}
		return $application->query_select(__CLASS__)->what(array(
			"id" => "ID",
			"name" => "Name"
		))->where(array(
			"Type" => $type,
			"Account" => $account_where
		))->to_array("id", "name");
	}

	/**
	 *
	 * @param unknown $locale
	 * @return string
	 */
	public static function type_names($locale = null) {
		return __(array(
			self::LabelType_Address => "Address",
			self::LabelType_Company => "Company",
			self::LabelType_Date => "Date",
			self::LabelType_Email => "Email",
			self::LabelType_Phone => "Phone",
			self::LabelType_URL => "Website",
			self::LabelType_Other => "Other"
		), $locale);
	}

	/**
	 *
	 * @return string
	 */
	public function type_name() {
		return $this->Name . " " . avalue($this->type_names(), $this->Type, 'Unknown Type');
	}

	/**
	 *
	 * @param unknown $types
	 * @param unknown $account
	 * @return \zesk\Ambigous
	 */
	public static function label_type_options(Application $app, $types = null, $account = null) {
		$table = $app->object_table_name(__CLASS__);
		$account_where = null;
		if (!empty($account)) {
			$account_where = array(
				$account,
				null
			);
		}
		$where = array(
			"Account" => $account_where
		);
		if ($types !== null) {
			$where['Type'] = $types;
		}
		$rows = $app->query_select(__CLASS__)->what(array(
			"id" => "ID",
			"name" => "Name",
			"type" => "Type"
		))->where($where)->to_array("id");

		$result = array();
		$type_names = self::type_names();
		foreach ($rows as $id => $row) {
			$name = $row['name'];
			$type_name = avalue($type_names, $row['type'], 'Unknown-' . $row['type']);
			$result[$type_name][$id] = $name;
		}
		return $result;
	}
}

