<?php
class Module_jQueryTimer extends Module_JSLib {
	protected $javascript_paths = array(
		"/share/jquerytimer/jquery.timer.js"
	);
	public static function hooks() {
		zesk::add_hook('Timestamp::formatting', __CLASS__ . "::view_date_formatting");
	}
	public static function view_date_formatting(Timestamp $timestamp, array $formatting) {
		$formatting['delta'] = html::tag("span", array(
			"data-timer" => $formatting['seconds'],
			"data-unit-minimum" => zesk::get1("Module_JQueryTimer::unit_minimum;Timestamp::formatting::unit_minimum", "second")
		), avalue($formatting, 'delta', ''));
		return $formatting;
	}
}