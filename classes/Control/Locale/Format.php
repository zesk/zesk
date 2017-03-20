<?php

namespace zesk;

class Control_Locale_Format extends Control_Select {
	protected $options = array(
		'options' => array(
			'en' => "English",
			'fr' => "French",
			'es' => "Spanish"
		)
	);

	protected static $locale_to_settings = array(
		'en' => array(
			'date-3' => '{WWWW}, {MMMM} {DDD} {YYYY}',
			'date-2' => '{MMMM} {D}, {YYYY}',
			'date-1' => '{MMM} {D}, {YYYY}',
			'date-0' => '{M}/{DD}/{YY}',
			'time-0' => '{12h}:{mm} {AMPM}',
			'decimal-point' => '.',
			'thousands-separator' => ','
		),

		// 		'fr' => array(
		// 			'date-3' => '{WWWW} {D} {MMMM} {YYYY}',
		// 			'date-2' => '{D} {MMMM} {YYYY}',
		// 			'date-1' => '{M} {DDD} {YYYY}',
		// 			'date-0' => '{MM}/{DD}/{YY}',
		// 			'time-0' => '{12hh}:{mm} {AMPM}',
		// 			'decimal-point' => ',',
		// 			'thousands-separator' => ' '
		// 		),
		'es' => array(
			'date-3' => '{WWWW}, {d} de {MMMM} de {YYYY}',
			'date-2' => '{D} de {MMMM} de {YYYY}',
			'date-1' => '{D} de {MMM} de {YYYY}',
			'date-0' => '{D}/{M}/{YY}',
			'time-0' => '{hh}:{mm}',
			'decimal-point' => ',',
			'thousands-separator' => '.'
		)
	);

	public function submit() {
		$val = $this->value();
		$settings = avalue(self::$locale_to_settings, $val, array());
		$this->object->set($settings);
		return parent::submit();
	}
}
