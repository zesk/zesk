<?php
declare(strict_types=1);


namespace zesk\Net;

use zesk\Module as BaseModule;
use zesk\URL;

use zesk\Exception_Syntax;
use zesk\Exception_Configuration;
use zesk\Exception_Class_NotFound;
use zesk\Exception_Unsupported;

class Module extends BaseModule {
	/**
	 * @return void
	 * @throws Exception_Configuration
	 * @throws Exception_Unsupported
	 */
	public function initialize(): void {
		parent::initialize();
		$this->application->registerFactory('netClient', [$this, 'clientFactory']);
	}

	/**
	 * @param string $url
	 * @param array $options
	 * @return Client
	 * @throws Exception_Syntax
	 * @throws Exception_Class_NotFound
	 */
	public function clientFactory(string $url, array $options = []): Client {
		$scheme = strtolower(URL::scheme($url));
		$scheme = ($scheme === 'https') ? 'http' : $scheme;
		$app = $this->application;
		$class = __NAMESPACE__ . strtoupper($scheme) . '\\Client';
		$result = $app->factory($class, $app, $url, $options);
		assert($result instanceof Client);
		return $result;
	}
}
