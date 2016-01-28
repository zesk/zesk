<?php

class Module_reCAPTCHA extends Module {

	function initialize() {
		$result = true;
		foreach (to_list("site_key;secret_key") as $key) {
			if ($this->has_option($key)) {
				zesk::set("Control_reCAPTCHA::$key", $this->option($key), false);
			} else {
				log::error("RECAPTCHA is misconfigured (no {key})", compact("key"));
				Module_Critical::alert("RECAPTCHA is misconfigured (no $key)");
				$result = false;
			}
		}
		if (!parent::initialize()) {
			return false;
		}
		return $result;
	}
}
