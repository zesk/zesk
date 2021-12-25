<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage contact
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */
namespace zesk;

/**
 * Contact_Person
 *
 * @author kent
 */
class Contact_Person extends Contact_Info {
	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Contact_Info::label_type()
	 */
	public function label_type() {
		return false;
	}

	/**
	 * Informal greeting
	 *
	 * @return string
	 */
	public function greeting_name() {
		$names = [
			"Nickname",
			'FirstName',
			'LastName',
		];
		foreach ($names as $name) {
			$name = $this->member($name);
			if ($name) {
				return $name;
			}
		}
		return null;
	}

	/**
	 *
	 * @return string
	 */
	public function full_name() {
		$result = [];
		$names = [
			"Prefix",
			"FirstName",
			"MiddleName",
			"LastName",
			"Suffix",
		];
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
