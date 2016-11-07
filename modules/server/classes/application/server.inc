<?php
class Application_Server extends Application {
	
	/**
	 *
	 * @var Server_Platform
	 */
	public $platform = null;
	public $file = __FILE__;
	public $modules = array(
		"sqlite3",
		"server",
		"cron",
		"cloud",
		"apache",
		"bootstrap",
		"jquery",
		"footerlog",
		"lessphp"
	);
	public $hooks = array(
		"log",
		"Database"
	);
	public static function preconfigure(array $options = array()) {
		zesk::application_class(__CLASS__);
		
		zesk::command_path(path(zesk::root("command")));
		zesk::command_path(path(Module::path('server'), "command"));
		
		zesk::document_root(zesk::application_root("site"));
	}
	public function hook_head(zesk\Request $request, zesk\Response_Text_HTML $response, zesk\Template $template) {
		$response->css("/css/server.css", array(
			"root_dir" => zesk::document_root()
		));
	}
	public static function platform() {
		$application = self::instance();
		return $application->platform;
	}
	public function hook_construct() {
		$this->platform = Server_Platform::factory();
		Template::defaults("platform", $this->platform);
	}
}
