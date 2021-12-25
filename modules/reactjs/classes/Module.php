<?php declare(strict_types=1);
/**
 *
 */
namespace zesk\ReactJS;

use zesk\Application;
use zesk\Request;
use zesk\Response;
use zesk\Route;
use zesk\Template;
use zesk\HTML;
use zesk\Router;
use zesk\Configuration_Parser;
use zesk\File;
use zesk\Net_HTTP_Client;
use zesk\Net_HTTP;
use zesk\Exception_File_NotFound;
use zesk\Exception_System;
use zesk\Net_HTTP_Client_Exception;
use zesk\ArrayTools;
use zesk\MIME;
use zesk\JSON;
use zesk\Directory;
use zesk\Route_Method;
use zesk\Exception_NotFound;

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
	 * Directory where .env file should be loaded
	 *
	 * @var string
	 */
	private $dot_env_path = null;

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Module::initialize()
	 */
	public function initialize(): void {
		parent::initialize();
		$this->dot_env_path($this->option("dot_env_path", $this->application->path()));
		if ($this->option_bool("routes_enabled")) {
			$this->application->hooks->add(Application::class . "::router_matched", [
				$this,
				"router_matched",
			]);
		}
	}

	private function react_content(Request $request, Response $response) {
		$request = new Request($this->application, $request);
		$request->path("/");
		return $this->static_handler($request, $response);

		$content = file_get_contents($this->application->document_root("index.html"));
		$scripts = HTML::extract_tags("script", $content);
		foreach ($scripts as $script) {
			$src = $script->option("src");
			if (begins($src, "/static/js/") && strpos($src, "bundle.")) {
				return $content;
			}
		}
		$prefix = HTML::tag("script", [
			"src" => "/static/js/bundle.js",
		], "");
		return str_replace("</body>", $prefix . "</body>", $content);
	}

	/**
	 * @return Route
	 */
	protected function react_page_route(Router $router) {
		$module = $this;
		return new Route_Method($router, null, [
			"method" => fn (Request $request, Response $response) => $module->react_content($request, $response),
			"arguments" => [
				"{request}",
				"{response}",
			],
			Route::OPTION_OUTPUT_HANDLER => Response::CONTENT_TYPE_RAW,
		]);
	}

	/**
	 * If a route is marked as react, then intercept it and replace the Route with a metho which returns the index page.
	 *
	 * This will only run on development systems, usually.
	 *
	 * @param Application $app
	 * @param Request $request
	 * @param Router $router
	 * @param Route $route
	 * @return \zesk\Route|\zesk\Request|NULL
	 */
	public function router_matched(Application $app, Request $request, Router $router, Route $route) {
		if ($route->option_bool("react") && $request->method() === Net_HTTP::METHOD_GET && !$request->prefer_json()) {
			return $this->react_page_route($router)->request($request);
		}
		return null;
	}

	/**
	 *
	 * @throws Exception_File_NotFound
	 * @return string
	 */
	private function asset_manifest() {
		$docroot = $this->application->document_root();
		foreach ([
			'asset-manifest.json',
			'manifest.json',
		] as $name) {
			$asset_manifest = path($docroot, $name);
			if (file_exists($asset_manifest)) {
				return $asset_manifest;
			}
		}

		throw new Exception_File_NotFound($asset_manifest);
	}

	/**
	 * List of associated classes
	 *
	 * @var array
	 */
	public function hook_head(Request $request, Response $response, Template $template): void {
		$app = $this->application;
		$docroot = $app->document_root();
		if (ends($docroot, "/build")) {
			$source = path($docroot, "index.html");

			try {
				$asset_manifest = $this->asset_manifest();
				$assets = JSON::decode(File::contents($asset_manifest));
				$src = "/" . $assets['main.js'];
				$response->javascript($src, [
					"root_dir" => $docroot,
				]);

				if (isset($assets['main.css'])) {
					$response->css($assets['main.css'], [
						"root_dir" => $docroot,
					]);
				}
			} catch (\zesk\Exception_NotFound $e) {
				$app->logger->emergency("Asset manifest not found {asset_manifest} {e}", [
					"asset_manifest" => $asset_manifest,
					"e" => $e,
				]);
			} catch (\zesk\Exception_Syntax $e) {
				$app->logger->emergency("Unable to parse asset file {asset_manifest} {e}", [
					"asset_manifest" => $asset_manifest,
					"e" => $e,
				]);
			}
		} else {
			if ($this->_proxy_prefix($request->host())) {
				$response->javascript("/static/js/bundle.js", [
					"is_route" => true,
				]);
			}
		}
	}

	/**
	 *
	 * @param string $index_html
	 * @return string
	 */
	private function extract_script_names($index_html) {
		$script_names = [];
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
	public function hook_routes(Router $router): void {
		$router->add_route("static/*", [
			"weight" => "first",
			"method" => [
				$this,
				"static_handler",
			],
			"arguments" => [
				"{request}",
				"{response}",
			],
		]);
		$router->add_route("sockjs-node/*", [
			"weight" => "first",
			"method" => [
				$this,
				"not_found_handler",
			],
			"arguments" => [
				"{request}",
				"{response}",
			],
		]);
	}

	/**
	 * Getter/setter for dot-env path
	 *
	 * @param string $set
	 * @return this|$string
	 */
	public function dot_env_path($set = null) {
		if ($set !== null) {
			$set = $this->application->paths->expand($set);
			Directory::must($set);
			$this->dot_env_path = $set;
			return $this;
		}
		return $this->dot_env_path;
	}

	/**
	 *
	 * @return string
	 */
	private function _proxy_prefix($default_host, $default_port = 3000) {
		if ($this->proxy_prefix) {
			return $this->proxy_prefix;
		}
		$app = $this->application;
		$config = $app->configuration->path_set(__CLASS__);
		$dotenv = path($this->dot_env_path, ".env");
		if (!file_exists($dotenv)) {
			$this->application->logger->error("{dotenv} needs to be created to support ReactJS proxy", [
				"dotenv" => $dotenv,
			]);
			return null;
		}
		$conf = Configuration_Parser::factory("conf", File::contents($dotenv));
		$settings = $conf->process();
		$host = aevalue($_SERVER, 'HOST', $settings->get("host", $default_host));
		$port = aevalue($_SERVER, 'PORT', $settings->get("port", $default_port));
		return $this->proxy_prefix = "http://$host:$port";
	}

	/**
	 *
	 * @param Request $request
	 * @param string $path
	 * @return Net_HTTP_Client
	 */
	private function _proxy_path(Request $request) {
		$proxy_prefix = $this->_proxy_prefix($request->host());
		if ($proxy_prefix === null) {
			return null;
		}
		$http = new Net_HTTP_Client($this->application);
		$http->proxy_request($request, $proxy_prefix);

		try {
			$http->go();
		} catch (Net_HTTP_Client_Exception $e) {
			$this->application->logger->error("{method} Unable to proxy request {url::path} to {proxy_prefix}: {message}", [
				"method" => __METHOD__,
				"proxy_prefix" => $proxy_prefix,
				"message" => $e->getMessage(),
			] + $request->variables() + ArrayTools::kprefix($request->url_variables(), "url::"));

			throw new Exception_File_NotFound($http->url());
		}
		return $http;
	}

	public function not_found_handler(Request $request, Response $response): void {
		$response->status(Net_HTTP::STATUS_FILE_NOT_FOUND);
	}

	/**
	 *
	 * @param Request $request
	 * @param Response $response
	 */
	public function json_handler(Request $request, Response $response): void {
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
				if (!$http) {
					throw new Exception_System("Unable to determine local hostname to connect to");
				}
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
				$debug = "\n" . $e->filename();
			}
			$response->status(Net_HTTP::STATUS_FILE_NOT_FOUND, "Not found");
			$response->content_type(MIME::from_filename($request->path()));
			return "/* ReactJS File not found" . $debug . " */";
		}
	}

	/**
	 *
	 * @param Net_HTTP_Client $client
	 * @param Response $response
	 */
	private function copy_to_response(Net_HTTP_Client $client, Response $response): void {
		$response->status($client->response_code(), $client->response_message());
		$response->content_type($client->content_type());
		$response->content = $client->content();
	}
}
