<?php
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
	 * Override in subclasses
	 * 
	 * @var string
	 */
	protected $repository_class = null;
	
	/**
	 * 
	 */
	protected $repository_types = array();
	
	/**
	 *
	 */
	public function testConfiguration() {
		$this->path = rtrim($this->configuration->path_get([
			__CLASS__,
			"repository_path"
		]), "/");
		$this->url = rtrim($this->configuration->path_get([
			__CLASS__,
			"repository_url"
		]), "/");
		$path = $this->path;
		foreach ([
			"path",
			"$path/trunk",
			"$path/tags"
		] as $p) {
			$this->assertTrue(is_dir($p), "is_dir($p)");
		}
		$this->assertStringIsURL($this->url);
		$this->assertTrue(class_exists($this->repository_class), "\$this->repository_class = \"$this->repository_class\" does not exist");
	}
	
	/**
	 * @depends testConfiguration
	 */
	public function testFactory() {
		$this->assertNotCount(0, $this->repository_types, "Must initialize " . get_class($this) . "->repository_types to a non-zero list of types");
		foreach ($this->repository_types as $repository_type) {
			$repo = Repository::factory($this->application, $repository_type);
			$this->assertIsClass($repo, $this->repository_class);
		}
	}
}