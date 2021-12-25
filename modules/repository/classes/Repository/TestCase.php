<?php declare(strict_types=1);
namespace zesk;

class Repository_TestCase extends PHPUnit_TestCase {
	/**
	 *
	 * @var string
	 */
	protected $path = null;

	/**
	 *
	 * @var string
	 */
	protected $url = null;

	/**
	 *
	 * @var array
	 */
	protected $repository_options = [];

	/**
	 * Override in subclasses
	 *
	 * @var string
	 */
	protected $repository_class = null;

	/**
	 *
	 */
	protected $repository_types = [];

	/**
	 *
	 * @return string
	 */
	public function loadConfiguration() {
		$this_class = get_class($this);
		$config_path = [
			$this_class,
			"repository_path",
		];
		$config_url = [
			$this_class,
			"repository_url",
		];
		$this->path = rtrim($this->configuration->path_get($config_path), "/");
		$this->url = rtrim($this->configuration->path_get($config_url), "/");
		$this->assertNotEmpty($this->path, map("Configuration {config_path} is not set up", [
			"config_path" => $config_path,
		]));
		$this->assertNotEmpty($this->url, map("Configuration {config_url} is not set up", [
			"config_url" => $config_url,
		]));
		return $this->path;
	}

	/**
	 *
	 */
	public function testConfiguration() {
		$this->loadConfiguration();
		// Do not assume repo is created here
		$this->assertStringIsURL($this->url);
		$this->assertTrue(class_exists($this->repository_class), "\$this->repository_class = \"$this->repository_class\" does not exist");
		return $this->path;
	}

	/**
	 * @depends testConfiguration
	 */
	public function testFactory($path) {
		$this->assertNotCount(0, $this->repository_types, "Must initialize " . get_class($this) . "->repository_types to a non-zero list of types");
		$repo = null;
		$this->repository_options = to_array($this->configuration->path_get([
			get_class($this),
			"repository_options",
		]));
		foreach ($this->repository_types as $repository_type) {
			$repo = Repository::factory($this->application, $repository_type, $path, [
				"test_option" => "dude",
			] + $this->repository_options);
			$this->assertInstanceOf($this->repository_class, $repo);
			$this->assertEquals($path, $repo->path());
			$this->assertEquals($repo->option("test_option"), "dude");
		}
		return $repo;
	}
}
