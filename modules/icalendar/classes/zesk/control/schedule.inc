<?php
namespace zesk;

/**
 *
 * @see $ZESK_ROOT/modules/schedule/theme/zesk/control/schedule.tpl
 * @author kent
 *
 */
class Control_Schedule extends Control {
	/**
	 * When executing child, traverse the parent model
	 *
	 * @var boolean
	 */
	protected $traverse = true;

	/**
	 *
	 * @var array $options
	 */
	protected $options = array(
		'is_input_group' => true,
	);

	/**
	 *
	 * @var Control_Dropdown
	 */
	protected $widget_unit = null;

	/**
	 *
	 * @var Control_Dropdown
	 */
	protected $widget_dayofweek = null;

	/**
	 *
	 * @var Control_Dropdown
	 */
	protected $widget_dayofmonth = null;

	/**
	 *
	 * @var Control_Dropdown
	 */
	protected $widget_hourofday = null;

	/**
	 * Schedule
	 *
	 * @var Schedule
	 */
	public function model() {
		return new Schedule($this->option_array("default"));
	}

	/**
	 * Setup our widgets and the relationships between them
	 *
	 * {@inheritdoc}
	 *
	 * @see Widget::initialize()
	 */
	public function initialize() {
		$locale = $this->application->locale;
		/*
		 * Time of day
		 */
		$schedule_frequencies = array(
			'day' => $locale->__('Daily'),
			'week' => $locale->__('Weekly'),
			'month' => $locale->__('Monthly'),
		);

		/*
		 * Time of day
		 */
		$schedule_hourofday = array();
		$timestamp = Time::now();
		$timestamp->minute(0)->second(0);
		for ($i = 0; $i <= 23; $i++) {
			$timestamp->hour($i);
			$schedule_hourofday[$i] = $locale->__('Control_Schedule:=at {hourofday}', array(
				'hourofday' => $timestamp->format($locale, $this->option('format_hourofday', $locale->__('Control_Schedule::format_hourofday:={12h} {ampm}'))),
			));
		}

		/*
		 * Day of week
		 */
		$schedule_dayofweek = array();
		$timestamp = Date::now();
		$first = $this->option('first_dayofweek', 1);
		for ($i = $first; $i < $first + 7; $i++) {
			$timestamp->weekday($i);
			$schedule_dayofweek[$i] = $locale->__('Control_Schedule:=on {dayofweek}', array(
				'dayofweek' => $timestamp->format($locale, $this->option('format_dayofweek', '{WWWW}')),
			));
		}

		/*
		 * Day of month
		 */
		$schedule_dayofmonth = array();
		for ($i = 1; $i <= 28; $i++) {
			$schedule_dayofmonth[$i] = $locale->__('Control_Schedule:=on the {dayofmonth_ordinal}', array(
				'dayofmonth' => $i,
				'dayofmonth_ordinal' => $locale->ordinal($i),
			));
		}

		$column_prefix = $this->column_prefix();
		/* @var $w Control_Dropdown */
		$this->child($this->theme_variables['widget_unit'] = $this->widget_unit = $w = $this->widget_factory("zesk\\Control_Dropdown")
			->names("unit", null, $column_prefix . "unit")
			->control_options($schedule_frequencies)
			->required(true)
			->set_option("onupdate", "function (value) {
				zesk.log(\"value is \" + value);
				\$(\"#${column_prefix}dayofweek\").toggle(value === \"week\");
				\$(\"#${column_prefix}dayofmonth\").toggle(value === \"month\");
			}"));
		$w->wrap("div", ".input-group-btn");

		$this->child($this->theme_variables['widget_dayofweek'] = $this->widget_dayofweek = $w = $this->widget_factory("zesk\\Control_Dropdown")
			->names("weekday", null, $column_prefix . "dayofweek")
			->control_options($schedule_dayofweek)
			->required(true));

		$w->wrap("div", ".input-group-btn");
		$this->child($this->theme_variables['widget_dayofmonth'] = $this->widget_dayofmonth = $w = $this->widget_factory("zesk\\Control_Dropdown")
			->names("monthday", null, $column_prefix . "dayofmonth")
			->control_options($schedule_dayofmonth)
			->default_value(1)
			->required(true));
		$w->wrap("div", ".input-group-btn");
		$this->child($this->theme_variables['widget_hourofday'] = $this->widget_hourofday = $w = $this->widget_factory("zesk\\Control_Dropdown")
			->names("hour", null, $column_prefix . "hourofday")
			->control_options($schedule_hourofday)
			->required(true)
			->default_value(9));
		$w->wrap("div", ".input-group-btn");
		parent::initialize();
	}

	public function validate() {
		$object = $this->object;
		return true;
	}

	private function column_prefix() {
		return $this->column() . '_';
	}
}
