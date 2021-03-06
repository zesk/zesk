<?php
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Control_Duration extends Control_Select {
	public function relative_to($column = null) {
		return $column !== null ? $this->set_option('relative_to', $column) : $this->option('relative_to');
	}

	public function initialize() {
		$locale = $this->application->locale;
		$max_duration = $this->option_integer("max_duration_minutes", 12 * 60);
		$duration_interval = $this->option_integer("duration_interval_minutes", 15);
		$options = array();
		$ts = new TimeSpan();
		for ($i = $duration_interval; $i <= $max_duration; $i += $duration_interval) {
			$ts->seconds(0)->add($i * 60);
			$options[$i] = $ts->format($locale, $locale->__('Control_Duration::duration_format:={hours}:{mm}'));
		}
		$this->control_options($options);
		parent::initialize();

		if (!$this->has_option('id')) {
			$this->id('control-duration-' . $this->response()->id_counter());
		}
		$relative_to = $this->relative_to();
		if ($relative_to) {
			$child = $this->parent()->child($relative_to);
			if ($child) {
				$update_func = 'duration_update(\'#' . $this->id() . '\', datetime);';
				$child->set_option('onchange', $update_func);
				$child->set_option('oninit', $update_func);
			}
		}
		$locale->__("Control_Duration:={duration} (ends at {end_time}");
		$this->theme_variables['data-format'] = $this->option("time_format", $locale->__("Control_Duration::time_format:={12hh}:{mm} {ampm}"));
	}

	public function render() {
		$response = $this->response();
		$response->javascript('/share/zesk/js/duration.js');
		$response->javascript('/share/zesk/js/zesk-date.js');
		return parent::render();
	}
}
