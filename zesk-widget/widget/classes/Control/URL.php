<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage default
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Control_URL extends Control_Text {
	private function _url_check($url) {
		if (URL::valid($url)) {
			return URL::normalize($url);
		}
		if (!$this->optionBool('autofix')) {
			return false;
		}
		$url_new = URL::repair($url);
		if ($url_new) {
			return URL::normalize($url_new);
		}
		$default_protocol = $this->option('default_protocol', 'http');
		if (URL::valid("$default_protocol://$url")) {
			return URL::normalize("$default_protocol://$url");
		}
		return false;
	}

	private function error_syntax(): void {
		$this->error($this->option('error_syntax', $this->_error_default()));
	}

	private function _error_default() {
		$protocols = ArrayTools::suffixValues($this->protocol_list(), '://');
		return $this->application->locale->__('{label} must begin with {protocol_phrase}', [
			'protocol_phrase' => $this->application->locale->conjunction($protocols),
		]);
	}

	private function error_protocol(): void {
		$this->error($this->option('error_protocol', $this->_error_default()));
	}

	public function protocol_list() {
		return to_list($this->option('protocol', 'http;https'));
	}

	protected function error_map() {
		return parent::error_map() + [
			'protocol_phrase' => $this->application->locale->conjunction($this->protocol_list()),
		];
	}

	public function multiple($set = null) {
		if ($set !== null) {
			$this->setOption('multiple', true);
			return $this;
		}
		return $this->optionBool('multiple');
	}

	public function validate(): bool {
		$temp = parent::validate();
		if (!$temp) {
			return false;
		}
		$value = trim($this->value());
		$protocols = $this->protocol_list();
		if ($this->multiple()) {
			$sep = $this->option('multiple_separator', "\n");
			$urls = explode($sep, $value);
			$new_value = [];
			$error_values = [];
			foreach ($urls as $u) {
				$u = trim($u);
				if (empty($u)) {
					continue;
				}
				$u = $this->_url_check($u);
				if ($u) {
					if (!in_array(URL::parse($u, 'scheme'), $protocols)) {
						$this->error_protocol();
						$error_values[] = $u;
					} else {
						$new_value[] = $u;
					}
				}
			}
			if (count($error_values)) {
				$new_value = array_merge($new_value, $error_values);
				$urls = implode($sep, $new_value);
				$this->value($urls);
				return false;
			}
			if ($this->required() && count($new_value) === 0) {
				if ($value) {
					$this->error_required();
				} else {
					$this->error_syntax();
				}
				return false;
			}
			$urls = implode($sep, $new_value);
			$this->value($urls);
			return true;
		}

		$new_value = $this->_url_check($value);
		if (!$new_value) {
			if ($this->required()) {
				$this->error_syntax();
				return false;
			}
			return true;
		}
		if (!in_array(URL::parse($new_value, 'scheme'), $protocols)) {
			$this->error_protocol();
			return false;
		}
		return true;
	}
}
