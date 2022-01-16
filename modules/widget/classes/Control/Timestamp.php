<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * Created on Tue Jul 15 16:35:38 EDT 2008
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Control_Timestamp extends Control {
	protected $options = [
		"allow_times" => true,
	];

	public function future_only($set = null) {
		if ($set !== null) {
			return $this->setOption("data-future-only", to_bool($set));
		}
		return $this->optionBool('data-future-only');
	}

	public function past_only($set = null) {
		if ($set !== null) {
			return $this->setOption("data-past-only", to_bool($set));
		}
		return $this->optionBool('data-past-only');
	}

	public function allow_times($set = null) {
		if ($set !== null) {
			return $this->setOption("allow_times", to_bool($set));
		}
		return $this->optionBool('allow_times');
	}

	public function time_control($set = null) {
		return $set === null ? $this->optionBool('time_control') : $this->setOption('time_control', to_bool($set));
	}

	public function load(): void {
		parent::load();
		$value = $this->value();
		if (empty($value)) {
			$this->value(null);
		} else {
			$ts = new Timestamp($value);
			$this->value($ts);
		}
	}

	public function null_string($set = null) {
		if ($set !== null) {
			return $this->setOption('null_string', $set);
		}
		return $this->option('null_string');
	}

	public function validate() {
		$value = $this->value();
		if (!$value instanceof Timestamp && !is_date($value)) {
			if ($this->null_string() !== null) {
				return true;
			}
			if ($this->required()) {
				$this->error_required();
				return false;
			}
			return true;
		}
		if ($this->has_errors()) {
			return false;
		}
		$ts = new Timestamp($value);
		if ($ts->is_empty() && $this->required()) {
			$this->error_required();
			return false;
		}
		if ($this->future_only() && !$ts->after(Timestamp::now())) {
			$this->error($this->option("error_future_only", "Please enter a \"{label}\" in the future."));
			return false;
		}
		if ($this->past_only() && !$ts->before(Timestamp::now())) {
			$this->error($this->option("error_past_only", "Please enter a \"{label}\" in the past."));
			return false;
		}
		return true;
	}
}
