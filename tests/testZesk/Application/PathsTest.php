<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk\Application;

use testZesk\TestCase;
use zesk\Directory;
use zesk\Exception\DirectoryCreate;
use zesk\Exception\DirectoryNotFound;
use zesk\Exception\DirectoryPermission;
use zesk\Exception\FilePermission;
use zesk\Exception\NotFoundException;

/**
 * @coversDefaultClass \zesk\Application\Paths
 */
class PathsTest extends TestCase
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
     * @return void
     * @throws NotFoundException
     * @covers ::which
     */
    public function test_which(): void
    {
        $command = 'ls';
        $this->assertFileExists($this->application->paths->which($command));
    }

    /**
     * @return void
     * @throws NotFoundException
     * @covers ::which
     */
    public function test_which_fail(): void
    {
        $command = 'notacommandtofind';
        $this->expectException(NotFoundException::class);
        $this->application->paths->which($command);
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
