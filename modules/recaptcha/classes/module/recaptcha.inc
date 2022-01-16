<?php declare(strict_types=1);
namespace zesk;

class Module_reCAPTCHA extends Module {
	public function initialize(): void {
		$result = true;
		$configuration = $this->application->configuration;
		foreach (to_list("site_key;secret_key") as $key) {
			if ($this->hasOption($key)) {
				$target = [
					"Control_reCAPTCHA",
					$key,
				];
				if (!$configuration->path_get($target)) {
					$configuration->path_set($target, $this->option($key));
				}
			} else {
				$this->application->logger->critical("RECAPTCHA is misconfigured (no {key})", compact("key"));
			}
		}
		parent::initialize();
	}
}
