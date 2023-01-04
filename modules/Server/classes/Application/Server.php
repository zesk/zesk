<?php declare(strict_types=1);
namespace zesk;

class Application_Server extends Application {
	/**
	 *
	 * @var Server_Platform
	 */
	public $platform = null;

	public $file = __FILE__;

	public $modules = [
		'sqlite3',
		'server',
		'cron',
		'cloud',
		'apache',
		'bootstrap',
		'jquery',
		'footerlog',
		'lessphp',
	];

	public $hooks = [
		'log',
		'Database',
	];

	public function beforeConfigure(array $options = []): void {
		$this->addZeskCommandPath(path($this->modules->path('server'), 'command'));
		$this->setDocumentRoot('site');
	}

	public function hook_head(Request $request, Response $response, Template $template): void {
		$response->css('/css/server.css', [
			'root_dir' => $this->documentRoot(),
		]);
	}

	public static function platform() {
		$application = self::instance();
		return $application->platform;
	}

	public function hook_construct(): void {
		$this->platform = Server_Platform::factory();

		$this->template->set('platform', $this->platform);
	}
}
