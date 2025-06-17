<?php
declare(strict_types=1);
/**
 *
 */

namespace testZesk;

use zesk\ArrayTools;
use zesk\Directory;

use zesk\Exception\ClassNotFound;
use zesk\Exception\ConfigurationException;
use zesk\Exception\DirectoryCreate;
use zesk\Exception\DirectoryPermission;
use zesk\Exception\FileNotFound;
use zesk\Exception\KeyNotFound;
use zesk\Exception\NotFoundException;
use zesk\Exception\ParameterException;
use zesk\Exception\ParseException;
use zesk\Exception\ProtocolException;
use zesk\Exception\SemanticsException;
use zesk\Exception\SystemException;
use zesk\HTML;
use zesk\HTTP;
use zesk\Request;
use zesk\Response;

/**
 *
 * @author kent
 * @see    TestApplication
 * @see    TestRequest
 * @coversDefaultClass \zesk\Response
 */
class ResponseTestBase extends TestApplicationTestBase {
	
	private Request $request;
	
	public function initialize(): void
	{
		parent::initialize();
		$this->testApplication->modules->loadMultiple([
			'PHPUnit',
			'Doctrine',
			'World',
		]);
	}
	
	/**
	 * @return Response
	 * @throws ClassNotFound
	 * @throws DirectoryCreate
	 * @throws DirectoryPermission
	 * @throws FileNotFound
	 * @throws ParameterException
	 * @throws ParseException
	 * @throws SemanticsException
	 */
	private function __testRequestResponse(): Response
	{
		$app = $this->testApplication;
		
		$publicRoot = $app->documentRoot('public');
		
		Directory::depend($publicRoot);
		
		$this->assertFalse($app->isConfigured());
		$app->configure();
		$this->assertTrue($app->isConfigured());
		
		$this->request = Request::factory($app, 'http://www.example.com/home');
		$wantedIP = '10.0.0.2';
		$this->request->initializeFromSettings([Request::OPTION_REMOTE_IP => $wantedIP] + $this->request->variables());
		
		return $this->requestRoundTrip($this->request, 'home', $wantedIP);
	}
	
	/**
	 * @return void
	 * @covers ::status ::statusMessage ::id
	 * @throws ClassNotFound
	 * @throws ConfigurationException
	 * @throws DirectoryCreate
	 * @throws DirectoryPermission
	 * @throws FileNotFound
	 * @throws NotFoundException
	 * @throws ParameterException
	 * @throws ParseException
	 * @throws SemanticsException
	 * @throws SystemException
	 */
	public function testStatus(): void
	{
		$newApplication = $this->testApplication;
		
		
		$response = $this->__testRequestResponse();
		
		$this->assertIsInteger($response->id());
		$this->assertEquals(HTTP::STATUS_OK, $response->status());
		$this->assertEquals('OK', $response->statusMessage());
	}
	
	/**
	 * @return void
	 * @covers ::isHTML ::isJSON ::makeJSON ::makeHTML ::setContentType
	 * @throws ClassNotFound
	 * @throws DirectoryCreate
	 * @throws DirectoryPermission
	 * @throws FileNotFound
	 * @throws ParameterException
	 * @throws ParseException
	 * @throws SemanticsException
	 */
	public function testResponseTypes(): void
	{
		$response = $this->__testRequestResponse();
		
		$this->assertTrue($response->isHTML());
		$this->assertFalse($response->isJSON());
		
		$this->assertEquals($response, $response->makeJSON());
		$this->assertFalse($response->isHTML());
		$this->assertTrue($response->isJSON());
		
		$this->assertEquals($response, $response->makeHTML());
		$this->assertTrue($response->isHTML());
		$this->assertFalse($response->isJSON());
		
		$this->assertEquals($response, $response->setContentType(Response::CONTENT_TYPE_JSON));
		$this->assertEquals('application/json', $response->contentType());
		$this->assertFalse($response->isHTML());
		$this->assertTrue($response->isJSON());
		
		
		$this->assertEquals($response, $response->setContentType('text/html'));
		$this->assertTrue($response->isHTML());
		$this->assertFalse($response->isJSON());
	}
	
	/**
	 * @return void
	 * @covers ::noCache ::setOutputHandler ::toJSON ::cacheSave ::setCacheFor
	 */
	public function testCacheRelated(): void
	{
		$response = $this->__testRequestResponse();
		
		$this->assertEquals($response, $response->noCache());
		$this->assertEquals($response, $response->setOutputHandler(Response::CONTENT_TYPE_HTML));
		
		$jsonResponse = [];
		$this->assertEquals($jsonResponse, ArrayTools::filterKeys($response->toJSON(), null, ['elapsed']));
		
		$response->setCacheFor(100);
		$response->cacheSave($this->testApplication->cacheItemPool(), $this->request->url());
	}
	
	/**
	 * @return void
	 * @covers ::noCache ::setOutputHandler ::id_counter
	 * @throws ClassNotFound
	 * @throws DirectoryCreate
	 * @throws DirectoryPermission
	 * @throws FileNotFound
	 * @throws ParameterException
	 * @throws ParseException
	 * @throws SemanticsException
	 */
	public function testIDCounter(): void
	{
		$response = $this->__testRequestResponse();
		
		$id = $response->id_counter();
		$this->assertEquals($id + 1, $response->id_counter());
	}
	
	/**
	 * @return void
	 * @covers ::title ::setTitle
	 * @throws ClassNotFound
	 * @throws DirectoryCreate
	 * @throws DirectoryPermission
	 * @throws FileNotFound
	 * @throws ParameterException
	 * @throws ParseException
	 * @throws SemanticsException
	 */
	public function testTitle(): void
	{
		$response = $this->__testRequestResponse();
		
		$this->assertEquals('', $response->title());
		$this->assertEquals($response, $response->setTitle('Hello, world'));
		$this->assertEquals('Hello, world', $response->title());
	}
	
	/**
	 * @covers ::htmlAttributes ::setHTMLAttributes
	 * @return void
	 * @throws ClassNotFound
	 * @throws DirectoryCreate
	 * @throws DirectoryPermission
	 * @throws FileNotFound
	 * @throws ParameterException
	 * @throws ParseException
	 * @throws SemanticsException
	 */
	public function test_htmlAttributes(): void
	{
		$response = $this->__testRequestResponse();
		
		$this->assertEquals([], $response->htmlAttributes());
		$sampleAttributes = [
			'class'            => 'nojs',
			'data-application' => 'thingamabob',
		];
		$this->assertEquals($response, $response->setHTMLAttributes($sampleAttributes));
		$this->assertEquals($sampleAttributes, $response->htmlAttributes());
	}
	
	/**
	 * @return void
	 * @covers ::fooBar
	 */
	public function test_application_basics(): void
	{
		$response = $this->__testRequestResponse();
		
		$sampleAttributes = [
			'class'            => 'bodystyle',
			'data-application' => 'poughkeepsie',
		];
		
		$this->assertEquals([], $response->bodyAttributes());
		$this->assertEquals($response, $response->setBodyAttributes($sampleAttributes));
		$this->assertEquals($sampleAttributes, $response->bodyAttributes());
	}
	
	/**
	 * @return void
	 * @covers ::metaKeywords ::setMetaKeywords
	 * @throws ClassNotFound
	 * @throws DirectoryCreate
	 * @throws DirectoryPermission
	 * @throws FileNotFound
	 * @throws KeyNotFound
	 * @throws ParameterException
	 * @throws ParseException
	 * @throws SemanticsException
	 */
	public function testMetaKeywords(): void
	{
		$response = $this->__testRequestResponse();
		
		$sampleKeywords = 'buy moar stuff';
		
		try {
			$this->assertEquals('', $response->metaKeywords());
			$this->fail('should throw ' . KeyNotFound::class);
		} catch (KeyNotFound) {
		}
		
		$this->assertEquals($response, $response->setMetaKeywords($sampleKeywords));
		$this->assertEquals($sampleKeywords, $response->metaKeywords());
	}
	
	/**
	 * @return void
	 * @covers ::setMetaDescription ::metaDescription
	 * @throws ClassNotFound
	 * @throws DirectoryCreate
	 * @throws DirectoryPermission
	 * @throws FileNotFound
	 * @throws KeyNotFound
	 * @throws ParameterException
	 * @throws ParseException
	 * @throws SemanticsException
	 */
	public function testMetaDescription(): void
	{
		$response = $this->__testRequestResponse();
		
		$sampleDesc = 'a website where you can purchase more things for your closets';
		
		try {
			$this->assertEquals('', $response->metaDescription());
			$this->fail('should throw ' . KeyNotFound::class);
		} catch (KeyNotFound) {
		}
		$this->assertEquals($response, $response->setMetaDescription($sampleDesc));
		$this->assertEquals($sampleDesc, $response->metaDescription());
	}
	
	/**
	 * @return void
	 * @covers ::pageTheme ::setPageTheme
	 * @throws ClassNotFound
	 * @throws DirectoryCreate
	 * @throws DirectoryPermission
	 * @throws FileNotFound
	 * @throws ParameterException
	 * @throws ParseException
	 * @throws SemanticsException
	 */
	public function testPageTheme(): void
	{
		$response = $this->__testRequestResponse();
		
		$this->assertEquals('page', $response->pageTheme());
		$this->assertEquals($response, $response->setPageTheme('text'));
		$this->assertEquals('text', $response->pageTheme());
	}
	
	/**
	 * @covers ::javascript ::inlineJavaScript
	 * @covers \zesk\Response\HTML::scripts
	 * @return void
	 * @throws ClassNotFound
	 * @throws DirectoryCreate
	 * @throws DirectoryPermission
	 * @throws FileNotFound
	 * @throws ParameterException
	 * @throws ParseException
	 * @throws ProtocolException
	 * @throws SemanticsException
	 */
	public function testJavascript(): void
	{
		$response = $this->__testRequestResponse();
		$nocache = false;
		$this->assertEquals($response, $response->javascript(['https://www.example.com/main.js'], [
			'nocache' => $nocache,
			'id'      => 'main',
		]));
		$this->assertEquals($response, $response->javascript(['https://www.example.com/last.js'], [
			'nocache' => $nocache,
			'after'   => 'main',
		]));
		$this->assertEquals($response, $response->javascript('https://www.example.com/first.js', [
			'before'  => 'main',
			'nocache' => $nocache,
		]));
		$this->assertEquals($response, $response->javascript('https://www.example.com/not-last.js', [
			'before'  => 'last',
			'nocache' => $nocache,
		]));
		$this->assertEquals($response, $response->inlineJavaScript('alert(\'boo!\');', [
			'before'  => 'first',
			'nocache' => $nocache,
		]));
		$expectedScripts = [];
		$expectedScripts[] = '<script>alert(\'boo!\');</script>';
		$expectedScripts[] = '<script src="https://www.example.com/first.js"></script>';
		$expectedScripts[] = '<script src="https://www.example.com/main.js" id="main"></script>';
		$expectedScripts[] = '<script src="https://www.example.com/not-last.js"></script>';
		$expectedScripts[] = '<script src="https://www.example.com/last.js"></script>';
		$scripts = $response->html()->scripts();
		$html = [];
		foreach ($scripts as $script) {
			$html[] = HTML::tag($script['name'], $script['attributes'], $script['content']);
		}
		$this->assertEquals($expectedScripts, $html);
	}
	
	/**
	 * @return void
	 * @covers \zesk\Response\HTML::shortcutIcon \zesk\Response\HTML::setShortcutIcon
	 * @throws ClassNotFound
	 * @throws DirectoryCreate
	 * @throws DirectoryPermission
	 * @throws FileNotFound
	 * @throws ParameterException
	 * @throws ParseException
	 * @throws ProtocolException
	 * @throws SemanticsException
	 */
	public function test_shortcutIcon(): void
	{
		$response = $this->__testRequestResponse();
		$shortcutIcon = 'https://www.example.coim/icon.png';
		// Has side effects (adds "icon")
		$this->assertEquals('', $response->html()->shortcutIcon());
		$this->assertEquals($response, $response->html()->setShortcutIcon($shortcutIcon));
		$this->assertEquals($shortcutIcon, $response->html()->shortcutIcon());
	}
	
	/**
	 * @param Request $rootRequest
	 * @param string  $uri
	 * @param string  $expected
	 * @return Response
	 * @throws ClassNotFound
	 * @throws FileNotFound
	 * @throws SemanticsException
	 */
	private function requestRoundTrip(Request $rootRequest, string $uri, string $expected): Response
	{
		$newApplication = $rootRequest->application;
		$anotherRequest = Request::factory($newApplication, $rootRequest);
		$anotherRequest->setPath($uri);
		$anotherResponse = $newApplication->main($anotherRequest);
		$this->assertEquals(Response::CONTENT_TYPE_HTML, $anotherResponse->contentType());
		$this->assertEquals($expected, $anotherResponse->content());
		return $anotherResponse;
	}
}
