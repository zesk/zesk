<?php
/**
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * Created on Tue Jul 15 16:24:32 EDT 2008
 */
namespace zesk;

class Control_Phone extends Control_Text {
	public static function clean($phone) {
		$phone = preg_replace('/[^-0-9x \t\+\.\-\(\)]/', "", $phone);
		$phone = str_replace("\t", " ", $phone);
		$phone = str_replace("  ", " ", $phone);
		return $phone;
	}

	protected function validate() {
		$value = $this->value();
		$value = self::clean($value);
		if (!is_phone($value)) {
			$this->error(__("{label} must be formatted like a phone, using digits or the following characters: + - ( ) . x", array(
				'label' => $this->label(),
			)));
			return false;
		}
		return parent::validate();
	}
}
