<?php
use zesk\Date;
use zesk\Control_Select;
use zesk\Locale;

/**
 *
 * @author kent
 *
 */
class Control_RRule extends \zesk\Control {
	protected $render_children = false;
	protected $child_widgets = array();
	protected function initialize() {
		$name = $this->name();
		$ww = array();
		$locale = $this->locale;

		$w = $this->widget_factory(Control_Select::class)
			->names($name . '_repeat', $locale->__('Repeat'))
			->control_options(self::repeat_options($locale))
			->noname($locale->__('None'));
		$this->child($w);
		$ww['repeat'] = $w;

		$w = $this->widget_factory('Control_Integer')->names($name . '_interval', $locale->__('Interval'));
		$this->child($w);
		$ww['interval'] = $w;

		$w = $this->widget_factory('Control_Pills')
			->names($name . '_weekday')
			->control_options($this->weekday_options())
			->multiple(true)
			->one_or_more(true);
		$this->child($w);
		$ww['weekday'] = $w;

		$w = $this->widget_factory('Control_Pills')
			->names($name . '_monthday')
			->control_options($this->monthday_options())
			->multiple(true)
			->one_or_more(true);
		$this->child($w);
		$ww['monthday'] = $w;

		$w = $this->widget_factory('Control_Pills')
			->names($name . '_yearly')
			->control_options($this->monthly_options())
			->multiple(true)
			->one_or_more(true);
		$this->child($w);
		$ww['month'] = $w;

		$w = $this->widget_factory('Control_Select')
			->names($name . '_on_which')
			->control_options($this->which_day_options())
			->noname("");
		$this->child($w);
		$ww['onwhich'] = $w;

		$w = $this->widget_factory('Control_Select')
			->names($name . '_on_day')
			->control_options($this->day_choice_options())
			->noname("");
		$this->child($w);
		$ww['onday'] = $w;

		$w = $this->widget_factory("Control_Date")->names($name . '_enddate', $locale->__('End Date'))->set_option('inline', true);
		$this->child($w);
		$ww['enddate'] = $w;

		$this->child_widgets = $ww;
		parent::initialize();
	}
	static function repeat_options(Locale $locale) {
		return $locale->__(array(
			'none' => 'No repeat',
			'daily' => 'Daily',
			'weekly' => 'Weekly',
			'monthly' => 'Monthly',
			'yearly' => 'Yearly'
		));
	}
	static function repeat_custom_options(Locale $locale) {
		return $locale->__(array(
			'daily' => 'Daily',
			'weekly' => 'Weekly',
			'monthly' => 'Monthly',
			'yearly' => 'Yearly'
		));
	}
	function weekday_options() {
		return Date::weekday_names($this->locale, true);
	}
	function monthly_options() {
		return Date::month_names($this->locale, true);
	}
	function monthday_options() {
		$result = array();
		for ($i = 1; $i <= 31; $i++) {
			$result[$i] = $i;
		}
		return $result;
	}
	function which_day_options() {
		return $this->locale->__(array(
			1 => "first",
			2 => "second",
			3 => "third",
			4 => "fourth",
			5 => "fifth",
			'last' => 'last'
		));
	}
	function day_choice_options() {
		return $this->weekday_options() + $this->locale->__(array(
			'day' => 'Day',
			'weekday' => 'Weekday',
			'weekend' => 'Weekend Day'
		));
	}
	function theme_variables() {
		return parent::theme_variables() + array(
			'child_widgets' => $this->child_widgets
		);
	}
}
