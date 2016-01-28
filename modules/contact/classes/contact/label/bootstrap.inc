<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/contact/classes/contact/label/bootstrap.inc $
 * @package zesk
 * @subpackage contact
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
class Contact_Label_Bootstrap {
	public static function bootstrap() {
		$labels = array(
			array(
				'Work', 
				'work', 
				Contact_Label::LabelType_Phone
			), 
			array(
				'Work', 
				'work', 
				Contact_Label::LabelType_Email
			), 
			array(
				'Work', 
				'work', 
				Contact_Label::LabelType_Address
			), 
			array(
				'Work', 
				'work', 
				Contact_Label::LabelType_URL
			), 
			array(
				'Home', 
				'home', 
				Contact_Label::LabelType_Phone
			), 
			array(
				'Home', 
				'home', 
				Contact_Label::LabelType_Email
			), 
			array(
				'Home', 
				'home', 
				Contact_Label::LabelType_Address
			), 
			array(
				'Home', 
				'home', 
				Contact_Label::LabelType_URL
			), 
			array(
				'Mobile', 
				null, 
				Contact_Label::LabelType_Phone
			), 
			array(
				'Main', 
				'work', 
				Contact_Label::LabelType_Phone
			), 
			array(
				'Home fax', 
				'home', 
				Contact_Label::LabelType_Phone
			), 
			array(
				'Work fax', 
				'work', 
				Contact_Label::LabelType_Phone
			), 
			array(
				'Pager', 
				null, 
				Contact_Label::LabelType_Phone
			), 
			array(
				'Other', 
				null, 
				Contact_Label::LabelType_Phone
			), 
			array(
				'Other', 
				null, 
				Contact_Label::LabelType_Email
			), 
			array(
				'Other', 
				null, 
				Contact_Label::LabelType_Address
			), 
			array(
				'Other', 
				null, 
				Contact_Label::LabelType_URL
			), 
			array(
				'Homepage', 
				'home', 
				Contact_Label::LabelType_URL
			), 
			array(
				'Anniversary', 
				null, 
				Contact_Label::LabelType_Date
			), 
			array(
				'Birthday', 
				null, 
				Contact_Label::LabelType_Date
			)
		);
		if (!Object::class_table_exists('Contact_Label_Group') || !Object::class_table_exists('Contact_Label')) {
			return "";
		}
		$result = array();
		foreach ($labels as $arr) {
			list($codename, $group_name, $type) = $arr;
			$group_name = Contact_Label_Group::register_group($group_name);
			$label = new Contact_Label(array(
				'CodeName' => $codename, 
				"Type" => $type, 
				"Name" => $codename, 
				"Account" => null, 
				"Group" => $group_name
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
