<?php
/**
 * $URL: http://code.marketacumen.com/zesk/trunk/modules/contact/classes/contact/label.inc $
 * @package zesk
 * @subpackage contact
 */

/**
 * Zesk_Contact_Label
 */
class Zesk_Contact_Label extends Object {
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
	public static function find_global($type, $name) {
		$fields = array(
			'Account' => null,
			'CodeName' => $name,
			"Type" => $type
		);
		return Object::class_query(__CLASS__)->what("ID", "ID")->where($fields)->one("ID", null);
	}
	public static function register_local($type, $name, $account) {
		$fields = array(
			'Account' => $account,
			'CodeName' => strtolower($name),
			"Type" => $type
		);
		
		$id = Object::class_query(__CLASS__)->what("ID", "ID")->where($fields)->one("ID", null);
		if ($id) {
			return $id;
		}
		$fields = array(
			'Account' => $account,
			'CodeName' => strtolower($name),
			"Name" => $name,
			'Type' => $type
		);
		return Object::class_query_insert(__CLASS__)->values($fields)->execute();
	}
	public static function label_options($type, $account = null) {
		$account_where = null;
		if (!empty($account)) {
			$account_where = array(
				$account,
				null
			);
		}
		return Object::class_query(__CLASS__)->what(array(
			"id" => "ID",
			"name" => "Name"
		))->where(array(
			"Type" => $type,
			"Account" => $account_where
		))->to_array("id", "name");
	}
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
	public function type_name() {
		return $this->Name . " " . avalue($this->type_names(), $this->Type, 'Unknown Type');
	}
	public static function label_type_options($types = null, $account = null) {
		$table = Object::class_table_name(__CLASS__);
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
		$rows = Object::class_query(__CLASS__)->what(array(
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

