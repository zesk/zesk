<?php
declare(strict_types=1);
/**
 * @test_modules Git
 * @test_phpunit true
 */

namespace zesk\Git;

use zesk\Repository;
use zesk\Repository\TestCase;
use zesk\Directory;
use zesk\Exception\Semantics;
use zesk\Exception\SyntaxException;
use zesk\StringTools;
use zesk\URL;

/**
 *
 * @author kent
 *
 */
class Repository_Git_Test extends TestCase {
	protected array $load_modules = ['git'];

	/**
	 * Override in subclasses
	 *
	 * @var string
	 */
	protected string $repository_class = Repository::class;

	/**
	 *
	 * @var array
	 */
	protected array $repository_types = [
		'git',
	];

	/**
	 *
	 * @depends testConfiguration
	 * @return string
	 */
	public function testURL() {
		$this->loadConfiguration();
		$url = $this->url;
		$this->assertTrue(URL::valid($url), "URL $url is not a valid URL");
		return $this->url;
	}

	/**
	 * @depends testFactory
	 */
	public function testInfo(Repository $repo): void {
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
		$this->assertNotEmpty($url, "Url \"$url\" is empty");
		$this->assertTrue(URL::valid($url), "Url \"$url\" is not valid");
		$path = $repo->path();
		$this->assertStringMatchesFormat('%agittest%A', $path);
		$url = $this->url;
		$this->assertTrue(URL::valid($url), "URL $url is not a valid URL");
		$repo->url($url);
		Directory::deleteContents($path);
		$this->assertTrue(Directory::isEmpty($path));
		$this->assertTrue($repo->needUpdate(), 'Repo should need update');
		$repo->update();
		$this->assertTrue($repo->validate());
		$this->assertFalse(Directory::isEmpty($path));
		$this->assertDirectoryExists(path($this->path, '.svn'));
		$this->assertFalse($repo->needUpdate(), 'Repo should no longer need update');
		$this->assertDirectoriesExist($this->pathCatenator($this->path, [
			'.svn',
			'trunk',
			'tags',
			'branches',
		]));
		$tags = toArray($this->configuration->getPath([
			__CLASS__,
			'tags_tests',
		]));
		foreach ($tags as $tag) {
			$this->assertFalse($repo->needUpdate(), 'Repo should no longer need update');
			$repo->setURL(StringTools::glue($url, '/', "tags/$tag"));
			$this->assertTrue($repo->needUpdate(), 'Repo should need update');
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
	 */
	public function testNoURL(Repository $repo) {
		$this->expectException(Semantics::class);
		$repo->setURL('');
		$path = $repo->path();
		$this->assertStringMatchesFormat('%agittest%A', $path);
		Directory::deleteContents($path);
		$repo->url();
		return $repo;
	}

	/**
	 * @depends testFactory
	 */
	public function testBADURL(Repository $repo): void {
		$this->expectException(SyntaxException::class);
		$repo->setURL('http:/localhost/path/to/git');
	}
}
