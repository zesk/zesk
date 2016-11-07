<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/control/schedule.inc $
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */

// Format similar to crontab scheduling
//
// minute hour day-of-month month day-of-week year
//
class Control_Schedule extends Control {
	function defaults() {
		$colname = $this->column();
		$schedule_code = Schedule::codeFromLanguage($this->option("default", "Every day at midnight"), "en");
		$schedule_text = Schedule::codeToLanguage($schedule_code, $this->locale());
		$this->object->set($colname . "_text", $schedule_text);
		$this->object->set($colname . "_code", $schedule_code);
	}
	function load() {
		$this->Schedule = null;
		$colname = $this->column();
		$input_name = $this->name();
		
		$schedule_code = null;
		$schedule_id = null;
		$schedule_text = null;
		$save_schedule_code = $this->option_bool('save_schedule_code');
		
		if (!$save_schedule_code) {
			$schedule_id = $this->request->get($input_name, null);
		}
		$schedule_text = $this->request->get($input_name . "_text", null);
		$schedule_code = Schedule::codeFromLanguage($schedule_text, $this->locale());
		$schedule_text = Schedule::codeToLanguage($schedule_code, $this->locale());
		
		if (!$save_schedule_code && $schedule_id) {
			$user = User::master();
			$schedule = new Schedule($schedule_id);
			if ($schedule->fetch() && $user->can("Schedule", "view", $schedule)) {
				$schedule_code = $schedule->ScheduleCode;
				$schedule_text = $this->request->get($input_name . "_text");
				$new_schedule_code = Schedule::codeFromLanguage($schedule_text, $this->locale());
				if ($new_schedule_code !== $schedule_code) {
					$schedule_id = null;
					$schedule_code = $new_schedule_code;
				}
			}
		}
		$object = $this->object;
		$object->set($colname . "_text", $schedule_text);
		if ($save_schedule_code) {
			$this->value($object, $schedule_code);
		} else {
			$this->value($object, $schedule_id);
			$object->set();
		}
	}
	function before_store() {
		if ($this->option_bool('save_schedule_code')) {
			return true;
		}
		$object = $this->object;
		$colname = $this->column();
		
		$schedule_code = $object->get($colname . "_code");
		$schedule_id = $object->get($colname);
		
		if (!$schedule_id && $schedule_code) {
			$fields = array();
			$fields['ScheduleCode'] = $schedule_code;
			$schedule = new Schedule($fields);
			if (!$schedule->store()) {
				$this->setError($colname, __("Unable to save schedule"));
				return false;
			}
			$object->set($colname, $schedule->id());
			$this->Schedule = $schedule;
		} else {
			$this->Schedule = null;
		}
		return true;
	}
	function after_store($succeeded = true) {
		if ($this->option_bool('save_schedule_code')) {
			return;
		}
		if (!$succeeded && $this->Schedule instanceof Schedule) {
			$db = $this->application->database_factory(); 
			$db->delete($this->Schedule->table(), array(
				"ID" => $this->Schedule->id()
			));
		}
	}
	function render() {
		$object = $this->object;
		$save_schedule_code = $this->option_bool('save_schedule_code');
		$input_name = $this->name();
		
		$attrs = array();
		$attrs['name'] = $input_name . '_text';
		$attrs['id'] = $input_name . '_text';
		
		$value = $object->get($input_name . "_text");
		$rows = $this->option('rows', 'auto');
		$cols = $this->option('cols', 60);
		if ($rows === 'auto') {
			$rows = clamp(1, intval((strlen($value) + ($cols - 1)) / $cols), 20);
		}
		$attrs['cols'] = $cols;
		$attrs['rows'] = $rows;
		$attrs['class'] = $this->option('class');
		
		$result = HTML::hidden($input_name, $this->value());
		if (!$save_schedule_code) {
			$code = $object->get($input_name . "_code");
			$result .= HTML::hidden($input_name . "_code", $code);
			if (false)
				$result .= "<br />$code";
		}
		$result .= HTML::tag("textarea", $attrs, "$value");
		
		return $this->render_finish($object, $result);
	}
}
