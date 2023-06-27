<?php
declare(strict_types=1);

namespace zesk\Repository;

use zesk\Exception\NotFoundException;
use zesk\UnitTest;

class TestCase extends UnitTest
{
	/**
	 *
	 * @var string
	 */
	protected string $path = '';

	/**
	 *
	 * @var string
	 */
	protected string $url = '';

	/**
	 *
	 * @var array
	 */
	protected array $repository_options = [];

	/**
	 * Override in subclasses
	 *
	 * @var string
	 */
	protected string $repository_class = '';

	/**
	 *
	 */
	protected array $repository_types = [];

	/**
	 *
	 * @return string
	 */
	public function loadConfiguration(): string
	{
		$this_class = get_class($this);
		$config_path = [
			$this_class,
			'repository_path',
		];
		$config_url = [
			$this_class,
			'repository_url',
		];
		$this->path = rtrim($this->configuration->getPath($config_path, ''), '/');
		$this->url = rtrim($this->configuration->getPath($config_url, ''), '/');
		$this->assertNotEmpty($this->path, map('Configuration "{config_path}" is not set up', [
			'config_path' => $config_path,
		]));
		$this->assertNotEmpty($this->url, map('Configuration "{config_url}" is not set up', [
			'config_url' => $config_url,
		]));
		return $this->path;
	}

	/**
	 *
	 */
	public function testConfiguration(): string
	{
		$this->loadConfiguration();
		// Do not assume repo is created here
		$this->assertStringIsURL($this->url);
		$this->assertTrue(class_exists($this->repository_class), "\$this->repository_class = \"$this->repository_class\" does not exist");
		return $this->path;
	}

	/**
	 * @depends testConfiguration
	 * @throws NotFoundException
	 */
	public function testFactory($path): Base
	{
		$this->assertNotCount(0, $this->repository_types, 'Must initialize ' . get_class($this) . '->repository_types to a non-zero list of types');
		$repo = null;
		$this->repository_options = toArray($this->configuration->getPath([
			get_class($this),
			'repository_options',
		]));
		foreach ($this->repository_types as $repository_type) {
			$repo = Base::factory($this->application, $repository_type, $path, [
				'test_option' => 'dude',
			] + $this->repository_options);
			$this->assertInstanceOf($this->repository_class, $repo);
			$this->assertEquals(rtrim($path, '/'), rtrim($repo->path(), '/'));
			$this->assertEquals($repo->option('test_option'), 'dude');
		}
		return $repo;
	}
}
