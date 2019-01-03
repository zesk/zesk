<?php
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Module_jQueryTimer extends Module_JSLib {
	protected $javascript_paths = array(
		"/share/jquerytimer/jquery.timer.js",
	);

	public function initialize() {
		$this->application->hooks->add(Timestamp::class . '::formatting', array(
			$this,
			"view_date_formatting",
		));
	}

	/**
	 *
	 * @param Timestamp $timestamp
	 * @param array $formatting
	 * @param array $options
	 * @return unknown
	 */
	public function view_date_formatting(Timestamp $timestamp, Locale $locale, array $formatting, array $options) {
		$application = $this->application;
		$attributes = array(
			"data-timer" => $formatting['seconds'],
			"data-unit-minimum" => $this->option("unit_minimum", $application->configuration->path_get(Timestamp::class . "::formatting::unit_minimum", "second")),
		);
		if (array_key_exists("format_future_zero", $options)) {
			$attributes['data-format-future-zero'] = $options["format_future_zero"];
		}
		if (array_key_exists("format_past_zero", $options)) {
			$attributes['data-format-past-zero'] = $options["format_past_zero"];
		}
		$formatting['delta'] = HTML::tag("span", $attributes, avalue($formatting, 'delta', ''));
		return $formatting;
	}
}
