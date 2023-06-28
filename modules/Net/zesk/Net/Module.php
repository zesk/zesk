<?php
declare(strict_types=1);


namespace zesk\Net;

use zesk\Exception\ClassNotFound;
use zesk\Exception\ConfigurationException;
use zesk\Exception\SyntaxException;
use zesk\Exception\UnsupportedException;
use zesk\Module as BaseModule;
use zesk\URL;

class Module extends BaseModule
{
	/**
	 * @return void
	 * @throws ConfigurationException
	 * @throws UnsupportedException
	 */
	public function initialize(): void
	{
		parent::initialize();
		$this->application->registerFactory('netClient', [$this, 'clientFactory']);
	}

	/**
	 * @param string $url
	 * @param array $options
	 * @return Client
	 * @throws SyntaxException
	 * @throws ClassNotFound
	 */
	public function clientFactory(string $url, array $options = []): Client
	{
		$scheme = strtolower(URL::scheme($url));
		$scheme = ($scheme === 'https') ? 'http' : $scheme;
		$app = $this->application;
		$class = __NAMESPACE__ . strtoupper($scheme) . '\\Client';
		$result = $app->factory($class, $app, $url, $options);
		assert($result instanceof Client);
		return $result;
	}
}
