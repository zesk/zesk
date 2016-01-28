<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/control/email.inc $
 * @package zesk
 * @subpackage widgets
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 * Created on Sun Apr 04 21:44:56 EDT 2010 21:44:56
 */
class Control_Email extends Control_Text {

	function accept_list($set = null) {
		return $set !== null ? $this->set_option('accept_list', to_bool($set)) : $this->option_bool('accept_list');
	}

	function validate() {
		$temp = parent::validate();
		$v = $this->value();
		if ($this->accept_list()) {
			$emails = arr::trim_clean(to_list($v, array(), ','));
			$bad_email = array();
			foreach ($emails as $email) {
				if (!is_email($email)) {
					$bad_email[] = $email;
				}
			}
			if ($bad_email) {
				$this->error(__("Control_Email::error_format:={label} contains some invalid emails: {bad_emails}", array(
					'bad_emails' => implode(", ", $bad_email)
				)));
				return false;
			}
			$this->value(implode(", ", $emails));
		} else if ($temp && strlen($v) > 0 && !is_email($v)) {
			$this->error(__("Control_Email::error_format:={label} must be formatted like an email, e.g. user@example.com.", $this->options));
			return false;
		}
		return $temp;
	}
}
