<?php
/**
 * @test_sandbox true
 */
namespace zesk;

class Router_Test extends Test_Unit {
	function test_Router() {
		$testx = new Router($this->application);
		
		$hash = md5(microtime());
		$template = $this->test_sandbox("Router-Test.tpl");
		file_put_contents($template, "<?php\necho \"$hash\";");
		
		$this->application->theme_path($this->test_sandbox());
		
		$url_pattern = "foo";
		$defaults = null;
		$match_options = null;
		$testx->add_route($url_pattern, array(
			"theme" => 'Router-Test'
		));
		
		$request = new Request(array(
			"url" => "http://test/"
		));
		$this->assert($testx->match($request) === null);
		
		$app = $this->application;
		$app->request();
		$app->request = $request = new Request(array(
			"url" => 'http://www.example.com/foo'
		));
		$app->response = $response = Response::instance($this->application);
		$match = $testx->match($request);
		$this->assert($match instanceof Route);
		$this->assert_equal($match->option("theme"), "Router-Test");
		$testx->execute($request);
		
		// Avoids doing header() in test code
		$response->set_option("skip_response_headers", true);
		$content = $response->render();
		
		$this->assert_contains($content, $hash);
		
		$testx->__sleep();
	}
	function test_cached() {
		$mtime = null;
		Router::cached($this->application, $mtime);
	}
}
