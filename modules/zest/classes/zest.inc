<?php
/**
 * @desc
 * @version    $URL: https://code.marketacumen.com/zesk/trunk/modules/zest/classes/zest.inc $
 * @author     $Author: kent $
 * @package    modules
 * @subpackage zesk_test
 * @copyright  Copyright (C) 2013, {company}. All rights reserved.
 */

/**
 *
 * @author kent
 *
 */
class Zest extends Application {
	public $file = __FILE__;
	protected $hooks = array(
		'db',
		'Settings',
		'log'
	);
	protected $modules = array(
		'bootstrap',
		'zest',
		'developer'
	);
	protected $internal_modules = array(
		'log_footer'
	);
	protected $classes = array(
		'Server',
		'Lock'
	);
	public function hook_request() {
		$request = parent::hook_request();
		$modules = $request->geta("module", array(), ";");
		if (count($modules)) {
			Module::load($modules);
		}
		return $request;
	}
	public static function widget_test(Request $request, Response $response) {
		$path = str_replace("/", "_", ltrim($request->path(), "/"));
		return Widget::factory("Control_Form", null, $this)->child(Widget::factory($path, null, $this))->execute();
	}
	public static function run_test($string) {
		$test = new Command_Test(array(
			"Command_Test",
			$string
		), array(
			"directory" => zesk::get("Application_Zesk_Test::test_directory", zesk::root()),
			"debug" => true,
			"verbose" => true,
			"no-database" => true,
			"no-sandbox" => true,
			'no-buffer' => true,
			"strict" => true,
			"command_local_open" => null
		));
		ob_start();
		$test->go();
		$content = ob_get_clean();
		echo html::tag('pre', $content);
	}

	public function preconfigure(array $options = array()) {
		zesk::autoload_path(zesk::root('command'), array(
			"class_prefix" => "command"
		));
		$this->configure_include(array(
			'zest.conf',
			'zest-' . php_uname('n') . ".conf"
		));

		log::file(zesk::application_root('log/zest.log'));
		log::level(log::DEBUG);
		$this->development(true);
		$this->register_class("Module_Daemon_Abyzou");
		$this->register_class("Module_Daemon_Belphegor");
		$this->register_class("Module_Daemon_Jinkininki");
		$this->register_class("Module_Daemon_Lamashtu");
		$this->register_class("Module_Daemon_Pontianak");

		Database::database_default("zest");
		dir::depend(zesk::application_root("/data/"));
		Database::register("zest", "sqlite3://localhost" . zesk::application_root("/data/zest.sqlite3"));
	}
}
