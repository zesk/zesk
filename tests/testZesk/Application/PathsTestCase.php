<?php
declare(strict_types=1);

namespace testZesk\Application;

use testZesk\TestCase;
use zesk\Directory;
use zesk\Exception\DirectoryCreate;
use zesk\Exception\DirectoryNotFound;
use zesk\Exception\DirectoryPermission;
use zesk\Exception\FilePermission;

/**
 * @coversDefaultClass \zesk\Application\Paths
 */
class PathsTestCase extends TestCase
{
	/**
	 * @return void
	 * @throws DirectoryNotFound
	 * @covers \zesk\Application\Paths::setTemporary
	 */
	public function test_setTemporary_not(): void
	{
		$this->expectException(DirectoryNotFound::class);
		$this->application->paths->setTemporary('./cache/foo');
	}

	/**
	 * @throws DirectoryPermission
	 * @throws DirectoryCreate
	 * @throws FilePermission
	 * @throws DirectoryNotFound
	 * @covers ::temporary ::expand ::setTemporary
	 */
	public function test_setTemporary_yes(): void
	{
		$oldTemp = $this->application->paths->temporary();
		$path = './cache/foo';
		Directory::depend($this->application->paths->expand($path));
		$this->application->paths->setTemporary($path);
		Directory::delete($this->application->paths->expand($path));
		Directory::depend($oldTemp);
		$this->application->paths->setTemporary($oldTemp);
	}
}
