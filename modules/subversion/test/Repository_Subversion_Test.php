<?php declare(strict_types=1);
/**
 * @test_modules Git
 * @test_phpunit true
 */
namespace zesk;

use zesk\Subversion\Repository;

/**
 *
 * @author kent
 *
 */
class Repository_Subversion_Test extends Repository_TestCase {
	/**
	 * Override in subclasses
	 *
	 * @var string
	 */
	protected $repository_class = Repository::class;

	/**
	 *
	 * @var array
	 */
	protected $repository_types = [
		'svn',
		'subversion',
	];

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Repository_TestCase::testConfiguration()
	 */
	public function testConfiguration() {
		$path = parent::testConfiguration();
		// 		$svn_meta = path($path, ".svn");
		// 		$this->assertTrue(is_dir($svn_meta), "is_dir($svn_meta)");
		return $path;
	}

	/**
	 *
	 * @return string
	 */
	public function testURL() {
		$this->loadConfiguration();
		$url = $this->url;
		$this->assertTrue(URL::valid($url), "URL $url is not a valid URL");
		return $this->url;
	}

	/**
	 * @depends testConfiguration
	 * {@inheritDoc}
	 * @see \zesk\Repository_TestCase::testFactory()
	 */
	public function testFactory($path) {
		return parent::testFactory($path);
	}

	/**
	 * @depends testFactory
	 */
	public function testInfo(Repository $repo): void {
		$this->loadConfiguration();
		$repo->url($this->url);
		$repo->update();
		$info = $repo->info();
		$this->assertArrayHasKeys([
			Repository::INFO_URL,
			'relative-url',
			'root',
			'uuid',
			'working-copy-path',
			'working-copy-schedule',
			'commit-author',
			'commit-date',
		], $info, 'Repository info missing keys');
		$this->assertTrue(URL::valid($info[Repository::INFO_URL]), 'URL is valid: ' . $info[Repository::INFO_URL]);
		$this->assertTrue(URL::valid($info['root']), 'URL is valid: ' . $info['root']);
	}

	/**
	 * @depends testFactory
	 * @depends testURL
	 */
	public function testUpdate(Repository $repo, $url) {
		$this->loadConfiguration();
		$path = $repo->path();
		$this->assertStringMatchesFormat('%asvntest%A', $path);
		$url = $this->url;
		$this->assertTrue(URL::valid($url), "URL $url is not a valid URL");
		$repo->url($url);
		Directory::delete_contents($path);
		$this->assertTrue(Directory::is_empty($path));
		$this->assertTrue($repo->need_update(), 'Repo should need update');
		$repo->update();
		$this->assertTrue($repo->validate());
		$this->assertFalse(Directory::is_empty($path));
		$this->assertDirectoryExists(path($this->path, '.svn'));
		$this->assertFalse($repo->need_update(), 'Repo should no longer need update');
		$this->assertDirectoriesExist($this->pathCatenator($this->path, [
			'.svn',
			'trunk',
			'tags',
			'branches',
		]));
		$branches_dir = path($this->path, 'branches');
		Directory::delete($branches_dir);
		$this->assertDirectoryNotExists($branches_dir, "Deleting of $branches_dir failed?");
		$this->assertTrue($repo->need_update(), 'Repo needs update after directory "branches" deleted');
		$repo->update();
		$this->assertDirectoryExists($branches_dir, "Deleting of $branches_dir failed?");
		$this->assertFalse($repo->need_update(), 'Repo does needs update after directory "branches" updated');

		$tags = to_array($this->configuration->path_get([
			__CLASS__,
			'tags_tests',
		]));
		foreach ($tags as $tag) {
			$this->assertFalse($repo->need_update(), 'Repo should no longer need update');
			$repo->url(glue($url, '/', "tags/$tag"));
			$this->assertTrue($repo->need_update(), 'Repo should need update');
			$repo->update();
			$this->assertDirectoriesExist($this->pathCatenator($this->path, [
				'.svn',
			]));
			$this->assertDirectoriesNotExist($this->pathCatenator($this->path, [
				'trunk',
				'tags',
				'branches',
			]));
			$tag_name_file = path($this->path, 'tag-name.txt');
			$this->assertFileExists($tag_name_file);
			$this->assertEquals($tag, trim(file_get_contents($tag_name_file)), "File should contain tag name $tag");
		}
		return $repo;
	}

	/**
	 * @depends testFactory
	 * @expectedException zesk\Exception_Semantics
	 */
	public function testNoURL(Repository $repo) {
		$repo->url(false);
		$path = $repo->path();
		$this->assertStringMatchesFormat('%asvntest%A', $path);
		Directory::delete_contents($path);
		$repo->url();
		return $repo;
	}

	/**
	 * @depends testFactory
	 * @expectedException zesk\Exception_Syntax
	 */
	public function testBADURL(Repository $repo) {
		$repo->url('http:/localhost/path/to/svn');
		return $repo;
	}
}
