<?php
class Router_Test extends Test_Unit {
	function test_Router() {
		$route_map = false;
		$defaults = null;
		$testx = new Router($route_map, $defaults);
		
		$hash = md5(microtime());
		$template = $this->test_sandbox("test.tpl");
		file_put_contents($template, "<?php\necho \"$hash\";");
		
		$url_pattern = "foo";
		$defaults = null;
		$match_options = null;
		$testx->add_route($url_pattern, array(
			"template" => $template
		));
		
		$request = new Request();
		$this->assert($testx->match($request) === null);
		
		$app = Application::instance();
		$app->request = $request = new Request('http://www.example.com/foo');
		$app->response = $response = Response::instance();
		$match = $testx->match($request);
		$this->assert($match instanceof Route);
		$this->assert_equal($match->option("template"), $template);
		$testx->execute($app);
		
		$this->assert_contains($response->content, $hash);
		
		$testx->__sleep();
	}
	function test_cached() {
		$mtime = null;
		Router::cached($mtime);
	}
	function test_singleton() {
		$mtime = null;
		Router::singleton();
	}
}
