<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage widgets
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 * Created on Sun Apr 04 21:44:56 EDT 2010 21:44:56
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Control_Email extends Control_Text {
	public function accept_list($set = null) {
		return $set !== null ? $this->setOption('accept_list', to_bool($set)) : $this->optionBool('accept_list');
	}

	public function validate() {
		$temp = parent::validate();
		$v = $this->value();
		$locale = $this->application->locale;
		if ($this->accept_list()) {
			$emails = ArrayTools::trim_clean(to_list($v, [], ','));
			$bad_email = [];
			foreach ($emails as $email) {
				if (!is_email($email)) {
					$bad_email[] = $email;
				}
			}
			if ($bad_email) {
				$this->error($locale->__("Control_Email::error_format:={label} contains some invalid emails: {bad_emails}", [
					'bad_emails' => implode(", ", $bad_email),
				]));
				return false;
			}
			$this->value(implode(", ", $emails));
		} elseif ($temp && strlen($v) > 0 && !is_email($v)) {
			$this->error($locale->__("Control_Email::error_format:={label} must be formatted like an email, e.g. user@example.com.", $this->options));
			return false;
		}
		return $temp;
	}
}
