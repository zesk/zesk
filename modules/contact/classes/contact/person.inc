<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/contact/classes/contact/person.inc $
 * @package zesk
 * @subpackage contact
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */

/**
 * Contact_Person
 *
 * @author kent
 */
class Contact_Person extends Contact_Info {
	function label_type() {
		return false;
	}
	function listWidgetList() {
		$spec = array();

		$f = widgets::view_link("Name", "Name", "{LastName} {Suffix}, {Prefix} {FirstName} {MiddleName}");
		$spec[$f->column()] = $f;

		$f = widgets::actions();
		$spec[$f->column()] = $f;

		return $spec;
	}
	function newWidgetList() {
		$spec = $this->editWidgetList();
		unset($spec["Created"]);
		unset($spec["Modified"]);
		return $spec;
	}

	/**
	 * Informal greeting
	 *
	 * @return string
	 */
	function greeting_name() {
		$names = array(
			"Nickname",
			'FirstName',
			'LastName'
		);
		foreach ($names as $name) {
			$name = $this->member($name);
			if ($name) {
				return $name;
			}
		}
		return null;
	}
	function full_name() {
		return $this->fullName();
	}
	function fullName() {
		$result = array();
		$names = array(
			"Prefix",
			"FirstName",
			"MiddleName",
			"LastName",
			"Suffix"
		);
		foreach ($names as $name) {
			if (!$this->member_is_empty($name)) {
				$result[] = $this->member($name);
			}
		}
		if (count($result) == 0) {
			$result[] = $this->member('Nickname');
		}
		return implode(" ", $result);
	}
}
