<?php
/**
 * 
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Module_ReactJS extends Module implements Interface_Module_Routes, Interface_Module_Head {
	
	/**
	 * 
	 * @var Interface_Settings
	 */
	private $proxy_prefix = null;
	
	/**
	 * List of associated classes
	 *
	 * @var array
	 */
	public function hook_head(Request $request, Response_Text_HTML $response, Template $template) {
		$app = $this->application;
		$doc_root = $app->document_root();
		if (ends($doc_root, "/build")) {
			$script_names = $this->extract_script_names(path($doc_root, "index.html"));
			foreach ($script_names as $script_name) {
				$response->javascript($script_name, array(
					"root_dir" => $doc_root
				));
			}
		} else {
			$response->javascript("/static/js/bundle.js", array(
				"is_route" => true
			));
		}
	}
	/**
	 * 
	 * @param string $index_html
	 * @return string
	 */
	private function extract_script_names($index_html) {
		$script_names = array();
		foreach (HTML::extract_tags("script", file_get_contents($index_html)) as $tag) {
			$script_names[] = $tag->src;
		}
		return $script_names;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \zesk\Interface_Module_Routes::hook_routes()
	 */
	public function hook_routes(Router $router) {
		$router->add_route("static/*", array(
			"weight" => "first",
			"method" => array(
				$this,
				"static_handler"
			),
			"arguments" => array(
				"{request}",
				"{response}"
			)
		));
		$router->add_route("sockjs-node/*", array(
			"weight" => "first",
			"method" => array(
				$this,
				"not_found_handler"
			),
			"arguments" => array(
				"{request}",
				"{response}"
			)
		));
	}
	private function _proxy_prefix() {
		if ($this->proxy_prefix) {
			return $this->proxy_prefix;
		}
		$app = $this->application;
		$config = $app->configuration->path_set(__CLASS__);
		$conf = Configuration_Parser::factory("conf", File::contents($app->application_root(".env")));
		$settings = $conf->process();
		$host = aevalue($_SERVER, 'HOST', $settings->host);
		$port = aevalue($_SERVER, 'PORT', $settings->port);
		return $this->proxy_prefix = "http://$host:$port";
	}
	/**
	 * 
	 * @param Request $request
	 * @param string $path
	 * @return Net_HTTP_Client
	 */
	private function _proxy_path(Request $request) {
		$proxy_prefix = $this->_proxy_prefix();
		$http = new Net_HTTP_Client();
		$http->proxy_request($request, $proxy_prefix);
		try {
			$http->go();
		} catch (Net_HTTP_Client_Exception $e) {
			$this->application->logger->error("{method} Unable to proxy request {url::path} to {proxy_prefix}: {message}", array(
				"method" => __METHOD__,
				"proxy_prefix" => $proxy_prefix,
				"message" => $e->getMessage()
			) + $request->variables() + arr::kprefix($request->url_parts(), "url::"));
		}
		return $http;
	}
	public function not_found_handler(Request $request, Response_Text_HTML $response) {
		$response->status(Net_HTTP::Status_File_Not_Found);
	}
	/**
	 * 
	 * @param Request $request
	 * @param Response_Text_HTML $response
	 */
	public function json_handler(Request $request, Response_Text_HTML $response) {
		$response->page_theme(null);
		$this->copy_to_response($this->_proxy_path($request), $response);
		$response->cache_for(5);
	}
	
	/**
	 * 
	 * @param Request $request
	 * @param Response_Text_HTML $response
	 */
	public function static_handler(Request $request, Response_Text_HTML $response) {
		$app = $this->application;
		$response->page_theme(null);
		try {
			if ($app->development()) {
				$http = $this->_proxy_path($request);
				$http->proxy_response($response);
				$response->cache_for(5);
			} else {
				$path = $request->path();
				$response->file($app->application_root(path("build", $path)));
				$response->cache_forever();
			}
		} catch (Exception_File_NotFound $e) {
			$response->status(Net_HTTP::Status_File_Not_Found, "Not found");
			$response->content = "ReactJS File not found";
		}
	}
	private function copy_to_response(Net_HTTP_Client $client, Response $response) {
		$response->status($client->response_code(), $client->response_message());
		$response->content_type($client->content_type());
		$response->content = $client->content();
	}
}
