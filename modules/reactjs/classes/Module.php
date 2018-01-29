<?php
/**
 *
 */
namespace zesk\ReactJS;

use zesk\Request;
use zesk\Response;
use zesk\Template;
use zesk\HTML;
use zesk\Router;
use zesk\Configuration_Parser;
use zesk\File;
use zesk\Net_HTTP_Client;
use zesk\Net_HTTP;
use zesk\Exception_File_NotFound;
use zesk\Net_HTTP_Client_Exception;
use zesk\ArrayTools;

/**
 *
 * @author kent
 *
 */
class Module extends \zesk\Module implements \zesk\Interface_Module_Routes, \zesk\Interface_Module_Head {

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
	public function hook_head(Request $request, Response $response, Template $template) {
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
			if ($this->_proxy_prefix()) {
				$response->javascript("/static/js/bundle.js", array(
					"is_route" => true
				));
			}
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

	/**
	 *
	 * @return string
	 */
	private function _proxy_prefix() {
		if ($this->proxy_prefix) {
			return $this->proxy_prefix;
		}
		$app = $this->application;
		$config = $app->configuration->path_set(__CLASS__);
		$dotenv = $app->path(".env");
		if (!file_exists($dotenv)) {
			$this->application->logger->error("{dotenv} needs to be created to support ReactJS proxy", array(
				"dotenv" => $dotenv
			));
			return null;
		}
		$conf = Configuration_Parser::factory("conf", File::contents($dotenv));
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
		if ($proxy_prefix === null) {
			return null;
		}
		$http = new Net_HTTP_Client($this->application);
		$http->proxy_request($request, $proxy_prefix);
		try {
			$http->go();
		} catch (Net_HTTP_Client_Exception $e) {
			$this->application->logger->error("{method} Unable to proxy request {url::path} to {proxy_prefix}: {message}", array(
				"method" => __METHOD__,
				"proxy_prefix" => $proxy_prefix,
				"message" => $e->getMessage()
			) + $request->variables() + ArrayTools::kprefix($request->url_variables(), "url::"));
		}
		return $http;
	}
	public function not_found_handler(Request $request, Response $response) {
		$response->status(Net_HTTP::Status_File_Not_Found);
	}
	/**
	 *
	 * @param Request $request
	 * @param Response $response
	 */
	public function json_handler(Request $request, Response $response) {
		$response->page_theme(null);
		$this->copy_to_response($this->_proxy_path($request), $response);
		$response->cache_for(5);
	}

	/**
	 *
	 * @param Request $request
	 * @param Response $response
	 */
	public function static_handler(Request $request, Response $response) {
		$app = $this->application;
		try {
			if ($app->development()) {
				$http = $this->_proxy_path($request);
				$http->proxy_response($response);
				$response->cache_for(5);
			} else {
				$path = $request->path();
				$response->file($app->path(path("build", $path)));
				$response->cache_forever();
			}
		} catch (Exception_File_NotFound $e) {
			$debug = "";
			if ($app->development()) {
				$debug = "<br />" . $e->filename();
			}
			$response->status(Net_HTTP::Status_File_Not_Found, "Not found");
			$response->content = "ReactJS File not found" . $debug;
		}
	}
	private function copy_to_response(Net_HTTP_Client $client, Response $response) {
		$response->status($client->response_code(), $client->response_message());
		$response->content_type($client->content_type());
		$response->content = $client->content();
	}
}
