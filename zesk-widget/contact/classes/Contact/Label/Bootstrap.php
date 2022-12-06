<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage contact
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

class Contact_Label_Bootstrap {
	public static function bootstrap(Application $application) {
		$labels = [
			[
				'Work',
				'work',
				Contact_Label::LabelType_Phone,
			],
			[
				'Work',
				'work',
				Contact_Label::LabelType_Email,
			],
			[
				'Work',
				'work',
				Contact_Label::LabelType_Address,
			],
			[
				'Work',
				'work',
				Contact_Label::LabelType_URL,
			],
			[
				'Home',
				'home',
				Contact_Label::LabelType_Phone,
			],
			[
				'Home',
				'home',
				Contact_Label::LabelType_Email,
			],
			[
				'Home',
				'home',
				Contact_Label::LabelType_Address,
			],
			[
				'Home',
				'home',
				Contact_Label::LabelType_URL,
			],
			[
				'Mobile',
				null,
				Contact_Label::LabelType_Phone,
			],
			[
				'Main',
				'work',
				Contact_Label::LabelType_Phone,
			],
			[
				'Home fax',
				'home',
				Contact_Label::LabelType_Phone,
			],
			[
				'Work fax',
				'work',
				Contact_Label::LabelType_Phone,
			],
			[
				'Pager',
				null,
				Contact_Label::LabelType_Phone,
			],
			[
				'Other',
				null,
				Contact_Label::LabelType_Phone,
			],
			[
				'Other',
				null,
				Contact_Label::LabelType_Email,
			],
			[
				'Other',
				null,
				Contact_Label::LabelType_Address,
			],
			[
				'Other',
				null,
				Contact_Label::LabelType_URL,
			],
			[
				'Homepage',
				'home',
				Contact_Label::LabelType_URL,
			],
			[
				'Anniversary',
				null,
				Contact_Label::LabelType_Date,
			],
			[
				'Birthday',
				null,
				Contact_Label::LabelType_Date,
			],
		];
		$clg_exists = $application->ormRegistry(Contact_Label_Group::class)->tableExists();
		$cl_exists = $application->ormRegistry(Contact_Label::class)->tableExists();
		if (!$clg_exists || !$cl_exists) {
			return '';
		}
		$result = [];
		foreach ($labels as $arr) {
			[$codename, $group_name, $type] = $arr;
			$group_name = Contact_Label_Group::register_group($application, $group_name);
			$label = $application->ormFactory(Contact_Label::class, [
				'CodeName' => $codename,
				'Type' => $type,
				'Name' => $codename,
				'Account' => null,
				'Group' => $group_name,
			]);
			if (!$label->exists()) {
				$result[] = $label->store()->id();
			}
		}
		if (count($result) === 0) {
			return '';
		}
		return implode(";\n", $result) . ";\n";
	}
}
