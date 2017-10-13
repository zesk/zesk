<?php
/**
 * 
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Module_DKIM extends Module {
	public function initialize() {
	}
	public function hook_configured() {
		$config = $this->application->configuration;
		$mapped_any = false;
		foreach (array(
			"public_key",
			"private_key",
			"selector",
			"domain"
		) as $name) {
			if ($config->deprecated("DKIM::$name", __CLASS__ . "::$name")) {
				$mapped_any = true;
			}
		}
		if ($mapped_any) {
			$this->inherit_global_options();
		}
	}
	public function enabled() {
		return !$this->option_bool('disabled');
	}
	/**
	 * Sign email with DKIM
	 *
	 * @todo Move to DKIM module!
	 * @todo Fix this
	 */
	private function _dkim_sign() {
		$smtp_send = $this->application->configuration->SMTP_URL;
		$public_key = $this->option("public_key");
		if ($public_key && $smtp_send) {
			if ($this->enabled()) {
				$domain = $this->option("domain");
				$private_key = $this->option("private_key");
				$selector = $this->option("selector");
				$dkim = new DKIM($domain, $public_key, $private_key, $selector);
				$this->headers = $dkim->sign(self::render_headers($this->headers), $this->body);
			}
		}
	}
}
