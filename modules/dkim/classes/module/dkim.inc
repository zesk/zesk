<?php
class Module_DKIM extends Module {
	public function initialize() {
	}
	public function hook_configured() {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		
		$zesk->configuration->deprecated("DKIM::public_key", __CLASS__ . "::public_key");
	}
	/**
	 * Sign email with DKIM
	 *
	 * @todo Move to DKIM module!
	 * @todo Fix this
	 */
	private function _dkim_sign() {
		$smtp_send = zesk::get('SMTP_URL');
		if (zesk::get('DKIM::public_key') && $smtp_send) {
			if (\DKIM::enabled()) {
				$dkim = new DKIM(zesk::get('DKIM::domain'), zesk::get('DKIM::public_key'), zesk::get('DKIM::private_key'), zesk::get('DKIM::selector'));
				$this->headers = $dkim->sign(self::render_headers($this->headers), $this->body);
			}
		}
	}
}