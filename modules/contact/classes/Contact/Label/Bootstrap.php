<?php
/**
 * @package zesk
 * @subpackage contact
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
namespace zesk;

class Contact_Label_Bootstrap {
	public static function bootstrap(Application $application) {
		$labels = array(
			array(
				'Work',
				'work',
				Contact_Label::LabelType_Phone,
			),
			array(
				'Work',
				'work',
				Contact_Label::LabelType_Email,
			),
			array(
				'Work',
				'work',
				Contact_Label::LabelType_Address,
			),
			array(
				'Work',
				'work',
				Contact_Label::LabelType_URL,
			),
			array(
				'Home',
				'home',
				Contact_Label::LabelType_Phone,
			),
			array(
				'Home',
				'home',
				Contact_Label::LabelType_Email,
			),
			array(
				'Home',
				'home',
				Contact_Label::LabelType_Address,
			),
			array(
				'Home',
				'home',
				Contact_Label::LabelType_URL,
			),
			array(
				'Mobile',
				null,
				Contact_Label::LabelType_Phone,
			),
			array(
				'Main',
				'work',
				Contact_Label::LabelType_Phone,
			),
			array(
				'Home fax',
				'home',
				Contact_Label::LabelType_Phone,
			),
			array(
				'Work fax',
				'work',
				Contact_Label::LabelType_Phone,
			),
			array(
				'Pager',
				null,
				Contact_Label::LabelType_Phone,
			),
			array(
				'Other',
				null,
				Contact_Label::LabelType_Phone,
			),
			array(
				'Other',
				null,
				Contact_Label::LabelType_Email,
			),
			array(
				'Other',
				null,
				Contact_Label::LabelType_Address,
			),
			array(
				'Other',
				null,
				Contact_Label::LabelType_URL,
			),
			array(
				'Homepage',
				'home',
				Contact_Label::LabelType_URL,
			),
			array(
				'Anniversary',
				null,
				Contact_Label::LabelType_Date,
			),
			array(
				'Birthday',
				null,
				Contact_Label::LabelType_Date,
			),
		);
		$clg_exists = $application->orm_registry(Contact_Label_Group::class)->table_exists();
		$cl_exists = $application->orm_registry(Contact_Label::class)->table_exists();
		if (!$clg_exists || !$cl_exists) {
			return "";
		}
		$result = array();
		foreach ($labels as $arr) {
			list($codename, $group_name, $type) = $arr;
			$group_name = Contact_Label_Group::register_group($application, $group_name);
			$label = $application->orm_factory(Contact_Label::class, array(
				'CodeName' => $codename,
				"Type" => $type,
				"Name" => $codename,
				"Account" => null,
				"Group" => $group_name,
			));
			if (!$label->exists()) {
				$result[] = $label->insert_sql();
			}
		}
		if (count($result) === 0) {
			return "";
		}
		return implode(";\n", $result) . ";\n";
	}
}
