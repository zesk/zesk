<?php

namespace zesk;

class Module_reCAPTCHA extends Module {

	function initialize() {
		$result = true;
		$configuration = $this->application->configuration;
		foreach (to_list("site_key;secret_key") as $key) {
			if ($this->has_option($key)) {
				$target = array("Control_reCAPTCHA", $key);
				if (!$configuration->path_get($target)) {
					$configuration->pave_set($target, $this->option($key));
				}
			} else {
				$this->application->logger->error("RECAPTCHA is misconfigured (no {key})", compact("key"));
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
