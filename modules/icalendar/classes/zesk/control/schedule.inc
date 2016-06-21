<?php

namespace zesk;

use \Control as Control;
use \Time as Time;
use \Date as Date;

/**
 * @see $ZESK_ROOT/modules/schedule/theme/zesk/control/schedule.tpl
 * @author kent
 *
 */
class Control_Schedule extends Control {
	/**
	 * 
	 * @var array $options
	 */
	protected $options = array(
		'is_input_group' => true
	);
	
	/**
	 * @var \Control_DropDown
	 */
	protected $widget_unit = null;
	/**
	 * @var \Control_DropDown
	 */
	protected $widget_dayofweek = null;
	/**
	 * @var \Control_DropDown
	 */
	protected $widget_dayofmonth = null;
	/**
	 * @var \Control_DropDown
	 */
	protected $widget_hourofday = null;
	
	/**
	 * Setup our widgets and the relationships between them
	 *  
	 * {@inheritDoc}
	 * @see Widget::initialize()
	 */
	public function initialize() {
		/*
		 * Time of day
		 */
		$schedule_frequencies = array(
			'week' => __('Weekly'),
			'month' => __('Monthly')
		);
		
		/*
		 * Time of day
		 */
		$schedule_hourofday = array();
		$timestamp = Time::now();
		$timestamp->minute(0)->second(0);
		for ($i = 0; $i <= 23; $i++) {
			$timestamp->hour($i);
			$schedule_hourofday[$i] = __('Control_Schedule:=at {hourofday}', array(
				'hourofday' => $timestamp->format($this->option('format_hourofday', __('Control_Schedule::format_hourofday:={12h} {ampm}')))
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
			$schedule_dayofweek[$i] = __('Control_Schedule:=on {dayofweek}', array(
				'dayofweek' => $timestamp->format($this->option('format_dayofweek', '{WWWW}'))
			));
		}
		
		/*
		 * Day of month
		 */
		$schedule_dayofmonth = array();
		for ($i = 1; $i <= 28; $i++) {
			$schedule_dayofmonth[$i] = __('Control_Schedule:=on the {dayofmonth_ordinal}', array(
				'dayofmonth' => $i,
				'dayofmonth_ordinal' => Locale::ordinal($i)
			));
		}
		
		$column_prefix = $this->column_prefix();
		
		$this->child($this->theme_variables['widget_unit'] = $this->widget_unit = $this->widget_factory("Control_DropDown")
			->names($column_prefix . "unit")
			->control_options($schedule_frequencies)
			->required(true)
			->set_option("onupdate", "function (value) {
				zesk.log(\"value is \" + value); 
				\$(\"#${column_prefix}dayofweek\").toggle(value === \"week\");
				\$(\"#${column_prefix}dayofmonth\").toggle(value === \"month\");
			}"));
		
		$this->child($this->theme_variables['widget_dayofweek'] = $this->widget_dayofweek = $this->widget_factory("Control_DropDown")
			->names($column_prefix . "dayofweek")
			->control_options($schedule_dayofweek)
			->required(true));
		
		$this->child($this->theme_variables['widget_dayofmonth'] = $this->widget_dayofmonth = $this->widget_factory("Control_DropDown")
			->names($column_prefix . "dayofmonth")
			->control_options($schedule_dayofmonth)
			->default_value(1)
			->required(true));
		
		$this->child($this->theme_variables['widget_hourofday'] = $this->widget_hourofday = $this->widget_factory("Control_DropDown")
			->names($column_prefix . "hourofday")
			->control_options($schedule_hourofday)
			->required(true)
			->default_value(9));
		
		parent::initialize();
	}
	private function column_prefix() {
		return $this->column() . '_';
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Widget::load()
	 */
	public function load() {
		$this->children_load();
		
		$column_prefix = $this->column_prefix();
		
		$object = $this->object;
		$value = $this->value();
		if (!$value instanceof Schedule) {
			$value = new Schedule();
		}
		/* @var $value Schedule */
		$value->frequency = 1;
		$value->unit = $object->get($column_prefix . '_unit');
		$value->weekday = $object->get($column_prefix . '_dayofweek');
		$value->monthday = $object->get($column_prefix . '_dayofmonth');
		$value->hour = $object->get($column_prefix . '_hourofday');
		
		$this->value($value);
	}
}