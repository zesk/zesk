<?php
namespace zesk;

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
	public function preconfigure(array $options = array()) {
		$this->zesk_command_path(path($this->modules->path("server"), "command"));
		$this->set_document_root("site");
	}
	public function hook_head(Request $request, Response_Text_HTML $response, Template $template) {
		$response->css("/css/server.css", array(
			"root_dir" => $this->document_root()
		));
	}
	public static function platform() {
		$application = self::instance();
		return $application->platform;
	}
	public function hook_construct() {
		$this->platform = Server_Platform::factory();
		
		$this->template->set("platform", $this->platform);
	}
}
