<?php declare(strict_types=1);
namespace zesk\WebApp;

use zesk\Exception_Configuration;
use zesk\ArrayTools;
use zesk\Directory;
use zesk\Kernel\Loader;
use zesk\Configuration_Loader;
use zesk\File;
use zesk\Router;
use zesk\StringTools;
use zesk\Server;
use zesk\Exception_Parse;
use zesk\Exception_Semantics;
use zesk\Timestamp;
use zesk\Application;
use zesk\Request;
use zesk\JSON;
use zesk\Server_Data;
use zesk\URL;
use zesk\Net_HTTP_Client;
use zesk\Exception_Syntax;
use zesk\Controller as zeskController;
use zesk\Response;
use zesk\Net_HTTP;

class Module extends \zesk\Module implements \zesk\Interface_Module_Routes {
	/**
	 * Attached to Server to track and modify load balancer health responses
	 *
	 * @var string
	 */
	public const SERVER_DATA_APP_HEALTH = __CLASS__ . '::app_health';

	/**
	 *
	 * @var string
	 */
	public const CONTROL_FILE_RESTART_APACHE = 'restart-apache';

	/**
	 *
	 * @var string
	 */
	public const OPTION_APP_ROOT_PATH = 'path';

	/**
	 *
	 * @var string
	 */
	public const OPTION_AUTHENTICATION_KEY = 'key';

	/**
	 *
	 * @var string
	 */
	public const OPTION_GENERATOR_CLASS = 'generator_class';

	/**
	 *
	 * @var string
	 */
	public const OPTION_GENERATOR_CLASS_DEFAULT = Generator_Apache::class;

	/**
	 *
	 * @var string
	 */
	private string $_app_root = '';

	/**
	 *
	 * @var ?Generator
	 */
	private ?Generator $generator = null;

	/**
	 *
	 * @var array
	 */
	protected array $modelClasses = [
		Instance::class,
		Site::class,
		Domain::class,
		Cluster::class,
		Repository::class,
	];

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Module::initialize()
	 */
	public function initialize(): void {
		$this->_app_root = $this->application->paths->expand($this->option(self::OPTION_APP_ROOT_PATH));
		if (empty($this->_app_root)) {
			throw new Exception_Configuration(__CLASS__ . '::' . self::OPTION_APP_ROOT_PATH, 'Requires the app root path to be set in order to work.');
		}
		if (!is_dir($this->_app_root)) {
			throw new Exception_Configuration(__CLASS__ . '::' . self::OPTION_APP_ROOT_PATH, 'Requires the app root path to be a directory in order to work.');
		}
		$this->application->hooks->add(Application::class . '::request', [
			$this,
			'register_domain',
		]);
	}

	/**
	 *
	 * @return \zesk\Server
	 */
	public function server(): Server {
		return Server::singleton($this->application);
	}

	/**
	 * Run deploy functionality
	 */
	public function hook_deploy(): void {
		$generator = $this->generator();
		$generator->deploy();
	}

	/**
	 *
	 * @param Application $application
	 * @param Request $request
	 */
	public function register_domain(Application $application, Request $request): void {
		$domain_name = $request->host();
		$item = $application->cache->getItem(__METHOD__);
		if ($item->isHit()) {
			$domains = $item->get();
			if (isset($domains[$domain_name])) {
				return;
			}
			$domains[$domain_name] = true;
			$item->set($domains);
			$application->cache->save($item);
		}
		$application->ormFactory(Domain::class, [
			'name' => $domain_name,
		])->register()->accessed();
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Interface_Module_Routes::hook_routes()
	 */
	public function hook_routes(Router $router): void {
		$router->addRoute(trim($this->option('route_prefix', 'webapp'), '/') . '(/{option action})*', [
			'controller' => Controller::class,
		]);
		$router->addRoute('.webapp(/{option action})*', [
			'controller' => Controller::class,
		]);
	}

	/**
	 * Fetch a file or directory beneath the app_root path
	 *
	 * @return string
	 */
	public function app_root_path($suffix = null) {
		if (!$this->_app_root) {
			throw new Exception_Configuration(__CLASS__ . '::path', 'Valid path to directory required ({path})', [
				'path' => $this->option(self::OPTION_APP_ROOT_PATH),
			]);
		}
		return $suffix === null ? $this->_app_root : path($this->_app_root, $suffix);
	}

	/**
	 * Fetch a file or directory beneath the app_root path
	 *
	 * @return string
	 */
	public function webapp_data_path($suffix = null) {
		return $this->app_root_path(".webapp/$suffix");
	}

	/**
	 * Where the binary to manage this application is located (generated by `$this->generate_binary()`)
	 *
	 * @return string
	 */
	public function binary() {
		return $this->application->paths->cache('webapp/public/index.php');
	}

	/**
	 *
	 * @return string
	 */
	public function key() {
		$key = $this->option(self::OPTION_AUTHENTICATION_KEY);
		if (!empty($key)) {
			return $key;
		}
		return md5(__FILE__);
	}

	/**
	 *
	 * @return Generator
	 */
	private function _generator() {
		$class = $this->option(self::OPTION_GENERATOR_CLASS, self::OPTION_GENERATOR_CLASS_DEFAULT);
		return $this->application->factory($class, $this->application);
	}

	/**
	 *
	 * @return Generator
	 */
	public function generator() {
		if ($this->generator) {
			return $this->generator;
		}
		return $this->generator = $this->_generator();
	}

	/**
	 * Generates the binary structure for serving up the webapp management module
	 */
	public function generate_binary() {
		$configurations = $this->application->loader->variables()[Configuration_Loader::PROCESSED] ?? null;

		$path = $this->application->paths->cache('webapp/public/');
		Directory::depend($path, 0o775);

		File::put(path($path, 'index.php'), "<?php\n\$app = require_once \"../webapp.config.php\");\n\$app->index();\n");

		$path = dirname($path);
		File::put(path($path, 'webapp.config.php'), file_get_contents($this->path('theme/webapp.config.tpl')));
		File::put(path($path, 'configuration.json'), json_encode($configurations));

		return true;
	}

	/**
	 * Scans `zesk\WebApp\Module::path` directory to find all `webapp.json` files, each representing an application
	 * instance. Applications may contain one or more sites served from document roots within each application.
	 *
	 * This can be extremely slow so do not do it often. (Add max_depth to Directory::list_recursive to speed up?)
	 *
	 * @return integer[string] Array of filename => modification time
	 */
	public function scan_webapp_json(array $walk_add = []) {
		// Include /.webapp.json, do not walk through . directories, or /vendor/, do not include directories in results
		$rules = [
			'rules_file' => [
				'#/webapp.json$#' => true,
				false,
			],
			'rules_directory_walk' => $walk_add + [
				"#/\.#" => false,
				'#/cache/#' => false,
				'#/vendor/#' => false,
				'#/node_modules/#' => false,
				true,
			],
			'rules_directory' => false,
			'add_path' => true,
		];
		if ($this->optionBool('debug')) {
			$rules['progress'] = $this->application->logger;
		}
		$files = Directory::list_recursive($this->_app_root, $rules);
		$result = [];
		foreach ($files as $f) {
			$result[$f] = filemtime($f);
		}
		ksort($result);
		return $result;
	}

	/**
	 *
	 */
	public function cached_webapp_json($rescan = false) {
		$app = $this->application;
		$cached = $app->cache->getItem(__METHOD__);
		if (!$cached->isHit()) {
			$files = $this->scan_webapp_json();
			$cached->set($files);
			$cached->expiresAfter(3600);
			$this->application->cache->saveDeferred($cached);
			return $files;
		}
		if (!$rescan) {
			$files = $cached->get();
			return $files;
		}
		$walk_add = [];
		foreach ($files as $file => $mtime) {
			if (is_file($file) && filemtime($file) === $mtime) {
				$walk_add['#' . preg_quote(rtrim(dirname($file), '/') . '/', '#') . '$#'] = false;
			} else {
				unset($files[$file]);
			}
		}
		$result = $files + $this->scan_webapp_json($walk_add);
		ksort($result);
		return $result;
	}

	/**
	 * Write and output our configuration files for our web server
	 *
	 * @return \zesk\WebApp\Generator
	 */
	public function generate_configuration($rescan = false) {
		$instances = ArrayTools::clean(ArrayTools::collapse($this->instance_factory($rescan), 'instance'), null);
		$generator = $this->generator();

		$generator->start();
		foreach ($instances as $instance) {
			if (!$instance) {
				var_dump($instance);
			}
			/* @var $instance Instance */
			$generator->instance($instance);
			foreach ($instance->sites as $site) {
				/* @var $site Site */
				$generator->site($site);
			}
		}
		$generator->finish();
		$changed = $generator->changed();
		if (count($changed) > 0) {
			$this->application->logger->info('{method} generator reported changed: {changed}', [
				'method' => __METHOD__,
				'changed' => $changed,
			]);
			$this->control_file(self::CONTROL_FILE_RESTART_APACHE, time());
		}
		return $generator;
	}

	/**
	 * Delete unreferenced objects in our database, lazily.
	 *
	 */
	public function hook_cron_cluster_minute(): void {
		$this->application->ormRegistry(Instance::class)->remove_dead_instances();
		$this->application->ormRegistry(Site::class)->remove_dead_instances();
	}

	/**
	 * Generate our vhost configuration from the database and trigger a web server restart if changes
	 * are required.
	 */
	public function hook_cron_minute(): void {
		$this->generate_configuration(true);
	}

	/**
	 * Alternate mechanism to trigger restart
	 *
	 * @param string $name
	 * @return string
	 */
	public function control_file_path($name) {
		$name = File::clean_path($name);
		$full = $this->webapp_data_path("control/$name");
		return $full;
	}

	/**
	 * Simple signal IPC using files
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return \zesk\WebApp\Module|mixed
	 */
	public function control_file($name, $value = null) {
		$full = $this->control_file_path($name);
		$dir = dirname($full);
		Directory::depend($dir, 0o775);
		if ($value !== null) {
			File::put($full, JSON::encode($value));
			$this->callHook('control_file', $full);
			return $this;
		}
		if ($value === false) {
			File::unlink($full);
			return $this;
		}
		if (file_exists($full)) {
			try {
				return JSON::decode(File::contents($full, '{}'));
			} catch (\Exception $e) {
				return true;
			}
		}
		return null;
	}

	/**
	 *
	 * @param string $register
	 * @return Instance[]
	 */
	public function instance_factory($register = false) {
		$app = $this->application;
		$webapps = $this->cached_webapp_json(false);

		$server = $app->objects->singleton(Server::class, $app);
		/* @var $server Server */
		if ($server->data(__CLASS__) === null) {
			$server->data(__CLASS__, 1);
		}

		$generator = $this->generator();
		$root = rtrim($this->app_root_path(), '/') . '/';

		$results = [];
		foreach ($webapps as $webapp_path => $modtime) {
			$subpath = StringTools::removePrefix($webapp_path, $root);
			$instance_struct = [
				'path' => $subpath,
			];

			try {
				if ($register) {
					$instance = Instance::register_from_path($app, $server, $generator, $webapp_path);
				} else {
					$instance = Instance::find_from_path($app, $server, $webapp_path);
				}
				if (!$instance) {
					$instance_struct['errors'][] = 'Instance not found.';
				} else {
					$instance_struct['instance'] = $instance;
					$instance_struct['appversion'] = $instance->appversion;
					$instance_struct['apptype'] = $instance->apptype;
				}
			} catch (Exception_Parse $e) {
				$instance_struct['errors'][] = 'Invalid JSON: ' . $e->getMessage();
			} catch (Exception_Semantics $e) {
				$instance_struct['errors'][] = 'Semantic error in JSON: ' . $e->getMessage();
			}
			$instance_struct['modified'] = Timestamp::factory($modtime, 'UTC')->format(null, '{YYYY}-{MM}-{DD} {hh}:{mm}:{ss} {ZZZ}');

			$results[$subpath] = $instance_struct;
		}

		if ($register && count($results) > 0) {
			foreach ($results as $result) {
				$instance = $result['instance'] ?? null;
				if ($instance) {
					$app->logger->debug('Refreshing instance #{id} {name} version', $instance->members());
					$instance->refresh_appversion();
				}
			}
			foreach ($results as $result) {
				$instance = $result['instance'] ?? null;
				if ($instance) {
					$app->logger->debug('Refreshing instance #{id} {name} repo', $instance->members());
					$instance->refresh_repository();
				}
			}
		}
		return $results;
	}

	/**
	 * Returns pair of salt/key
	 *
	 * @return array[2]
	 */
	public function generate_authentication() {
		$time = time();
		$hash = md5($time . '|' . $this->key());
		return [
			$time,
			$hash,
		];
	}

	/**
	 * Returns true
	 * @param int $time
	 * @param string $hash
	 * @return string|boolean
	 */
	public function check_authentication($time, $hash) {
		$now = time();
		if (!is_int($time)) {
			return 'time not integer: ' . type($time);
		}
		if ($time === 0) {
			return 'time is zero';
		}
		if (empty($hash)) {
			return 'missing hash';
		}
		$clock_skew = $this->option('authentication_clock_skew', 10); // 10 seconds
		$delta = abs($time - $now);
		if ($delta > $clock_skew) {
			return "clock skew: ($delta = abs($time - $now)) > $clock_skew";
		}
		$hash_check = md5($time . '|' . $this->key());
		if ($hash !== $hash_check) {
			return "hash check failed $hash !== $hash_check";
		}
		return true;
	}

	/**
	 * Check the authentication as a webapp request for any request
	 *
	 * @param Request $request
	 * @return string|true
	 */
	public function check_request_authentication(Request $request) {
		return $this->check_authentication($request->getInt(Controller::QUERY_PARAM_TIME), $request->get(Controller::QUERY_PARAM_HASH));
	}

	/**
	 *
	 * @param string $message
	 * @return self
	 */
	public function response_authentication_failed(Response $response, $message) {
		$response->setStatus(HTTP::STATUS_UNAUTHORIZED);
		return $response->json()->setData([
			'status' => false,
			'message' => "Authentication failed: $message",
		]);
	}

	/**
	 *
	 * @param string $action
	 */
	public function server_actions($action) {
		$app = $this->application;
		$servers = $app->ormRegistry(Server::class)
			->querySelect()
			->ormWhat()
			->link(Server_Data::class, [
				'alias' => 'd',
			])
			->addWhere('d.name', Module::class)
			->addWhere('d.value', serialize(1));
		$iterator = $servers->ormIterator();
		$results = [];
		foreach ($iterator as $server) {
			/* @var $server Server */
			$results[$server->name] = $this->server_action($server, $action);
		}
		return $results;
	}

	/**
	 *
	 * @param Server $server
	 * @param string $action
	 * @return array
	 */
	public function server_action(Server $server, $action, array $query = []) {
		$app = $this->application;
		$webapp = $app->webapp_module();
		$client = new Net_HTTP_Client($app);
		/* @var $webapp Module */
		$result = [
			'ip' => $server->ip4_internal,
		];

		try {
			[$time, $hash] = $webapp->generate_authentication();
			$url = URL::queryAppend('http://' . $server->ip4_internal . "/webapp/$action", [
				Controller::QUERY_PARAM_TIME => $time,
				Controller::QUERY_PARAM_HASH => $hash,
			] + $query);
			$client->url($url);
			$result['time'] = $time;
			$result['time_string'] = Timestamp::factory($time, 'UTC')->format($app->locale, Timestamp::FORMAT_JSON);
			$result['url'] = $url;
			$result['raw'] = $client->go();
			$result['status'] = true;
			$result['json'] = JSON::decode($result['raw']);
		} catch (Exception_Syntax $e) {
			// Do nothing
		} catch (\Exception $e) {
			$result['status'] = false;
			$result['message'] = $e->getMessage();
		}
		return $result;
	}

	/**
	 * Add a custom message handler to the current Router
	 *
	 * @param string $message
	 * @param array $options
	 * @return \zesk\Route
	 */
	public function add_message_route(Router $router, $message, array $options) {
		$module = $this;
		$options['before_hook'] = function (zeskController $controller) use ($module): void {
			if (($message = $module->check_request_authentication($controller->request())) !== true) {
				$module->response_authentication_failed($controller->response(), $message);
			}
		};
		$router->addRoute("/webapp/$message", $options);
		$router->addRoute(".webapp/$message", $options);
		return $this;
	}
}
