<?php
/**
 * $URL$
 * @package zesk
 * @subpackage address
 * @author kent
 * @copyright Copyright &copy; 2012, Market Acumen, Inc.
 */
class Module_World extends Module_JSLib {

	protected $javascript_paths = array(
		'/share/world/js/module.world.js'
	);

	protected $classes = array(
		'Currency',
		'City',
		'County',
		'Country',
		'Language',
		'Province'
	);

	public function hook_head(Request $request, Response_HTML $response, Template $template) {
		$currency = $this->hook_array("currency", array(), null);
		/* @var $currency Currency */
		if ($currency instanceof Currency) {
			$this->javascript_settings += array(
				'currency' => $currency->members("id;precision;format;name;code;symbol;fractional;fractional_units")
			);
		}
		parent::hook_head($request, $response, $template);
	}

	/*
	 public static function cron_week() {
	 $path = Module::path('address');
	 $urls = array();
	 // Dead as of 2014-02-13
	 // 		$urls = array(
	 // 			'http://www.iso.org/iso/list-en1-semic-3.txt',
	 // 			'http://www.iso.org/iso/list-fr1-semic.txt'
	 // 		);
	 $dest = dir::depend(path($path, 'bootstrap-data'));
	 foreach ($urls as $url) {
	 $contents = file_get_contents($url);
	 if ($contents) {
	 $dest_file = path($dest, basename($url));
	 log::notice("Updated $dest_file ...");
	 file_put_contents($dest_file, $contents);
	 } else {
	 log::error("Unable to get $url");
	 }
	 }
	 }
	 */

	function hook_schema_updated() {
		$__ = array(
			"method" => __METHOD__
		);
		$bootstrap = $this->option_bool("bootstrap_all");
		$bootstrap_country = $this->option_bool("bootstrap_country");
		$bootstrap_currency = $this->option_bool("bootstrap_currency");
		$bootstrap_language = $this->option_bool("bootstrap_language");
		log::debug("{method} begin", $__);
		if ($bootstrap || $bootstrap_country) {
			log::debug("{method} World_Bootstrap_Country", $__);
			World_Bootstrap_Country::factory()->bootstrap();
		}
		if ($bootstrap || $bootstrap_language) {
			log::debug("{method} World_Bootstrap_Language", $__);
			World_Bootstrap_Language::factory()->bootstrap();
		}
		if ($bootstrap || $bootstrap_currency) {
			log::debug("{method} World_Bootstrap_Currency", $__);
			World_Bootstrap_Currency::factory()->bootstrap();
		}
		log::debug("{method} ended", $__);
	}
}
