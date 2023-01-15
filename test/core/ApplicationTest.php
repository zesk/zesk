<?php
declare(strict_types=1);

/**
 *
 */

namespace zesk;

class ACMETest {
	public string $name;

	public function __construct(string $name) {
		$this->name = $name;
	}
}

/**
 *
 * @author kent
 *
 */
class ApplicationTest extends UnitTest {
	public function initialize(): void {
	}

	protected function acmeWidgetRegistry(Application $application, string $arg): ACMETest {
		return new ACMETest($arg);
	}

	public function test_add_registry(): void {
		$this->application->registerRegistry('acmeWidget', $this->acmeWidgetRegistry(...));

		$acme = $this->application->acmeWidgetRegistry('dude');
		$this->assertInstanceOf(ACMETest::class, $acme);
		$this->assertEquals('dude', $acme->name);
	}

	public function test_missing_factory(): void {
		$this->expectException(Exception_Unsupported::class);

		$this->application->missingFactory();
	}

	public function test_missing_module(): void {
		$this->expectException(Exception_NotFound::class);

		$this->application->missingModule();
	}

	public function test_application_basics(): void {
		$newApplication = $this->application->factory(TestAppplication::class, Kernel::singleton(), [
			'isSecondary' => true, 'version' => '1.0.0',
		]);

		$this->assertInstanceOf(Application::class, $newApplication);
		$this->assertTrue($newApplication->optionBool('isSecondary'));

		$this->assertEquals($newApplication::class, $newApplication->id());
		$this->assertEquals('1.0.0', $newApplication->version());
		$this->assertInstanceOf(Application::class, $newApplication->setVersion('2.0.0'));
		$this->assertEquals('2.0.0', $newApplication->version());

		$this->assertFalse($newApplication->isConfigured());

		$myConfigConf = $this->sandbox('newapp.conf');
		$myConfigJSON = $this->sandbox('newapp.json');
		File::put($myConfigConf, implode("\n", [
			'zesk___Application__version=3.0.0',
		]));
		File::put($myConfigJSON, JSON::encodePretty(['zesk\\Application' => ['version' => '4.0.0']]));

		$newApplication->configureInclude([$myConfigConf]);
		$newApplication->configureInclude([$myConfigJSON], false);

		$this->assertEquals([$myConfigConf, $myConfigJSON], $newApplication->includes());

		$this->assertFalse($newApplication->isConfigured());

		$newApplication->configure();

		$this->assertTrue($newApplication->isConfigured());

		$newApplication->configureInclude([]);
		$newApplication->reconfigure();
	}
}
