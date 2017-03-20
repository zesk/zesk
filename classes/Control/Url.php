<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/Control/Url.php $
 * @package zesk
 * @subpackage default
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2006, Market Acumen, Inc.
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
		if (!$this->option_bool('autofix')) {
			return false;
		}
		$url_new = URL::repair($url);
		if ($url_new) {
			return URL::normalize($url_new);
		}
		$default_protocol = $this->option("default_protocol", "http");
		if (URL::valid("$default_protocol://$url")) {
			return URL::normalize("$default_protocol://$url");
		}
		return false;
	}

	private function error_syntax() {
		$this->error($this->option('error_syntax', $this->_error_default()));
	}

	private function _error_default() {
		$protocols = arr::suffix($this->protocol_list(), "://");
		return __('{label} must begin with {protocol_phrase}', array(
			"protocol_phrase" => Locale::conjunction($protocols)
		));
	}
	private function error_protocol() {
		$this->error($this->option('error_protocol', $this->_error_default()));
	}

	function protocol_list() {
		return to_list($this->option('protocol', 'http;https'));
	}
	protected function error_map() {
		return parent::error_map() + array(
			'protocol_phrase' => Locale::conjunction($this->protocol_list())
		);
	}

	function multiple($set = null) {
		if ($set !== null) {
			$this->set_option('multiple', true);
			return $this;
		}
		return $this->option_bool('multiple');
	}

	function validate() {
		$temp = parent::validate();
		if (!$temp) {
			return false;
		}
		$value = trim($this->value());
		$protocols = $this->protocol_list();
		if ($this->multiple()) {
			$sep = $this->option('multiple_separator', "\n");
			$urls = explode($sep, $value);
			$new_value = array();
			$error_values = array();
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

