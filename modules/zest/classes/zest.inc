<?php
/**
 * @desc
 * @version    $URL: https://code.marketacumen.com/zesk/trunk/modules/zest/classes/zest.inc $
 * @author     $Author: kent $
 * @package    modules
 * @subpackage zesk_test
 * @copyright  Copyright (C) 2013, {company}. All rights reserved.
 */
use zesk\Directory;

/**
 * The Zest application allow for test running and writing directory within the browser for
 * immediate satisfaction running tests against any code you write.
 *
 * @author kent
 *
 */
class Zest extends zesk\Application {

	/**
	 * How the router is found
	 *
	 * @var string
	 */
	public $file = __FILE__;

	/**
	 *
	 * @var array
	 */
	protected $register_hooks = array(
		'zesk\Database',
		'zesk\Settings'
	);

	/**
	 *
	 * @var array
	 */
	protected $load_modules = array(
		'Bootstrap',
		'Zest',
		'MySQL',
		'Developer',
		'Logger_Footer'
	);
	protected $register_classes = array(
		'zesk\\Server',
		'zesk\\Lock'
	);
	public function hook_request() {
		$request = parent::hook_request();
		// Danger TODO
		$modules = $request->geta("module", array(), ";");
		if (count($modules)) {
			$this->modules->load($modules);
		}
		return $request;
	}
	public static function widget_test(zesk\Request $request, Response $response) {
		$path = str_replace("/", "_", ltrim($request->path(), "/"));
		return $this->widget_factory("zesk\\Control_Form", null, $this)->child($this->widget_factory($path))->execute();
	}
	public static function run_test(zesk\Request $reqeust, Response $response, $string) {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		$test = new Command_Test(array(
			"Command_Test",
			$string
		), array(
			"directory" => $zesk->configuration->path_get("Zest::test_directory", $zesk->paths->zesk()),
			"debug" => $reqeust->getb("debug"),
			"debug-command" => $reqeust->getb("debug-command"),
			"verbose" => $reqeust->getb("verbose"),
			"no-database" => true,
			"sandbox" => true,
			"strict" => true,
			"command_local_open" => null
		));

		$result = array();
		$result['test_pattern'] = $string;

		ob_start();
		$result['result'] = $test->go();
		$result['status'] = intval($result['result']) === 0;
		$result['content'] = ob_get_clean();

		$response->json($result);
	}
	public function preconfigure(array $options = array()) {
		global $zesk;

		$this->modules->load("Logger_File");

		/* @var $zesk \zesk\Kernel */
		$zesk->autoloader->path(zesk::root('command'), array(
			"class_prefix" => "command"
		));
		$this->configure_include(array(
			'zest.conf',
			'zest-' . php_uname('n') . ".conf"
		));
		$severity = $this->development(true) ? "debug" : "info";
		$zesk->logger->register_handler("file", zesk\Logger\File::factory($this->application_root('log/zest.log'), "a"), $zesk->logger->levels_select($severity));

		$this->register_class("Module_Daemon_Abyzou");
		$this->register_class("Module_Daemon_Belphegor");
		$this->register_class("Module_Daemon_Jinkininki");
		$this->register_class("Module_Daemon_Lamashtu");
		$this->register_class("Module_Daemon_Pontianak");

		zesk\Database::database_default("zest");
		Directory::depend($this->application_root("/data/"));

		$zesk->logger->notice("Default socket is " . ini_get("mysqli.default_socket"));
		//		zesk\Database::register("zest", "sqlite3://localhost" . $this->application_root("/data/zest.sqlite3"));
		zesk\Database::register("zest", "mysqli://zest:zest@localhost/zest");
		// create database zest;
		// grant all privileges on zest.* TO zest@localhost IDENTIFIED BY 'zest';
		// flush privileges;
	}
}
