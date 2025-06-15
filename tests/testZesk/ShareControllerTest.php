<?php
declare(strict_types=1);

namespace testZesk;

use zesk\Controller\Share;
use zesk\Directory;
use zesk\Exception\DirectoryNotFound;
use zesk\PHPUnit\TestCase;
use zesk\Route\Controller as ControllerRoute;

/**
 * @coversDefaultClass \zesk\Controller\Share
 */
class ShareControllerTest extends TestCase
{
	protected Share $share;

	public function setUp(): void
	{
		parent::setUp();
		$router = $this->application->router();
		$this->route = new ControllerRoute($router, '*', ['controller' => Share::class]);
		$share = $this->route->controller();
		$this->assertInstanceOf(Share::class, $share);
		$this->share = $share;
	}

	public function tearDown(): void
	{
		parent::tearDown();
		unset($this->route);
		unset($this->share);
	}

	/**
	 * @return void
	 * @throws DirectoryNotFound
	 * @covers ::addSharePath
	 */
	public function test_sharePath(): void
	{
		$this->share->addSharePath($this->test_sandbox(), 'dude');
	}

	/**
	 * @return void
	 * @throws DirectoryNotFound
	 * @covers ::addSharePath
	 */
	public function test_sharePath_not(): void
	{
		$this->expectException(DirectoryNotFound::class);
		$this->share->addSharePath(Directory::path($this->test_sandbox(), 'not-a-dir'), 'dude');
	}
}
