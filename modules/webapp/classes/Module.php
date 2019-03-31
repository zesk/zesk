<?php
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

class Module extends \zesk\Module implements \zesk\Interface_Module_Routes {
	/**
	 * Attached to Server to track and modify load balancer health responses
	 *
	 * @var string
	 */
	const SERVER_DATA_APP_HEALTH = __CLASS__ . "::app_health";

	/**
	 *
	 * @var string
	 */
	const CONTROL_FILE_RESTART_APACHE = "restart-apache";

	/**
	 *
	 * @var string
	 */
	const OPTION_AUTHENTICATION_KEY = "key";

	/**
	 *
	 * @var string
	 */
	const OPTION_GENERATOR_CLASS = "generator_class";

	/**
	 *
	 * @var unknown
	 */
	const OPTION_GENERATOR_CLASS_DEFAULT = Generator_Apache::class;

	/**
	 *
	 * @var string
	 */
	private $app_root = null;

	/**
	 *
	 * @var Generator
	 */
	private $generator = null;

	/**
	 *
	 * @var array
	 */
	protected $model_classes = array(
		Instance::class,
		Site::class,
		Domain::class,
		Cluster::class,
		Repository::class,
	);

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Module::initialize()
	 */
	public function initialize() {
		$this->app_root = $this->application->paths->expand($this->option("path"));
		if (empty($this->app_root)) {
			throw new Exception_Configuration(__CLASS__ . "::path", "Requires the app root path to be set in order to work.");
		}
		if (!is_dir($this->app_root)) {
			throw new Exception_Configuration(__CLASS__ . "::path", "Requires the app root path to be a directory in order to work.");
		}
		$this->application->hooks->add(Application::class . "::request", array(
			$this,
			"register_domain",
		));
	}

	/**
	 *
	 * @return \zesk\Server
	 */
	public function server() {
		return Server::singleton($this->application);
	}

	/**
	 * Run deploy functionality
	 */
	public function hook_deploy() {
		$generator = $this->generator();
		$generator->deploy();
	}

	/**
	 *
	 * @param Application $application
	 * @param Request $request
	 */
	public function register_domain(Application $application, Request $request) {
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
		$application->orm_factory(Domain::class, array(
			"name" => $domain_name,
		))->register()->accessed();
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Interface_Module_Routes::hook_routes()
	 */
	public function hook_routes(Router $router) {
		$router->add_route(trim($this->option("route_prefix", "webapp"), '/') . '(/{option action})', array(
			"controller" => Controller::class,
		));
		$router->add_route('.webapp(/{option action})', array(
			"controller" => Controller::class,
		));
	}

	/**
	 * Fetch a file or directory beneath the app_root path
	 *
	 * @return string
	 */
	public function app_root_path($suffix = null) {
		return $suffix === null ? $this->app_root : path($this->app_root, $suffix);
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
		return $this->application->paths->cache("webapp/public/index.php");
	}

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
		$configurations = avalue($this->application->loader->variables(), Configuration_Loader::PROCESSED);

		$path = $this->application->paths->cache("webapp/public/");
		Directory::depend($path, 0775);

		File::put(path($path, "index.php"), "<?php\n\$app = require_once \"../webapp.config.php\");\n\$app->index();\n");

		$path = dirname($path);
		File::put(path($path, "webapp.config.php"), file_get_contents($this->path("theme/webapp.config.tpl")));
		File::put(path($path, "configuration.json"), json_encode($configurations));

		return true;
	}

	/**
	 * Scans `zesk\WebApp\Module::path` directory to find all `webapp.json` files, each representing an application
	 * instance. Applications may contain one or more sites served from document roots within each application.
	 *
	 * @return integer[string] Array of filename => modification time
	 */
	public function scan_webapp_json(array $walk_add = array()) {
		// Include /.webapp.json, do not walk through . directories, or /vendor/, do not include directories in results
		$rules = array(
			"rules_file" => array(
				"#/webapp.json\$#" => true,
				false,
			),
			"rules_directory_walk" => $walk_add + array(
				"#/\.#" => false,
				"#/cache/#" => false,
				"#/vendor/#" => false,
				"#/node_modules/#" => false,
				true,
			),
			"rules_directory" => false,
			"add_path" => true,
		);
		if ($this->option_bool("debug")) {
			$rules['progress'] = $this->application->logger;
		}
		$files = Directory::list_recursive($this->app_root, $rules);
		$result = array();
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
		$files = $cached->get();
		if (!$rescan) {
			return $files;
		}
		$walk_add = array();
		foreach ($files as $file => $mtime) {
			if (is_file($file) && filemtime($file) === $mtime) {
				$walk_add['#' . preg_quote(rtrim(dirname($file), "/") . "/", "#") . "\$#"] = false;
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
	public function generate_configuration() {
		$instances = ArrayTools::clean(ArrayTools::collapse($this->instance_factory(false), "instance"), null);
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
			$this->application->logger->info("{method} generator reported changed: {changed}", array(
				"method" => __METHOD__,
				"changed" => $changed,
			));
			$this->control_file(self::CONTROL_FILE_RESTART_APACHE, time());
		}
		return $generator;
	}

	/**
	 * Delete unreferenced objects in our database, lazily.
	 *
	 */
	public function hook_cron_cluster_minute() {
		$this->application->orm_registry(Instance::class)->remove_dead_instances();
		$this->application->orm_registry(Site::class)->remove_dead_instances();
	}

	/**
	 * Generate our vhost configuration from the database and trigger a web server restart if changes
	 * are required.
	 */
	public function hook_cron_minute() {
		$this->generate_configuration();
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
		Directory::depend($dir, 0775);
		if ($value !== null) {
			File::put($full, JSON::encode($value));
			$this->call_hook("control_file", $full);
			return $this;
		}
		if ($value === false) {
			File::unlink($full);
			return $this;
		}
		if (file_exists($full)) {
			try {
				return JSON::decode(File::contents($full, "{}"));
			} catch (\Exception $e) {
				return true;
			}
		}
		return null;
	}

	/**
	 *
	 * @param string $register
	 * @return \zesk\WebApp\Instance[][]|string[][]
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
		$root = rtrim($this->app_root_path(), "/") . "/";

		$results = array();
		foreach ($webapps as $webapp_path => $modtime) {
			$subpath = StringTools::unprefix($webapp_path, $root);
			$instance_struct = array(
				'path' => $subpath,
			);

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
			$instance_struct["modified"] = Timestamp::factory($modtime, 'UTC')->format(null, '{YYYY}-{MM}-{DD} {hh}:{mm}:{ss} {ZZZ}');

			$results[$subpath] = $instance_struct;
		}

		if ($register && count($results) > 0) {
			foreach ($results as $result) {
				$instance = $result['instance'] ?? null;
				if ($instance) {
					$app->logger->debug("Refreshing instance #{id} {name} version", $instance->members());
					$instance->refresh_appversion();
				}
			}
			foreach ($results as $result) {
				$instance = $result['instance'] ?? null;
				if ($instance) {
					$app->logger->debug("Refreshing instance #{id} {name} repo", $instance->members());
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
		$hash = md5($time . "|" . $this->key());
		return array(
			$time,
			$hash,
		);
	}

	/**
	 * Returns true
	 * @param integer $time
	 * @param string $hash
	 * @return string|boolean
	 */
	public function check_authentication($time, $hash) {
		$now = time();
		if (!is_integer($time)) {
			return "time not integer: " . type($time);
		}
		if ($time === 0) {
			return "time is zero";
		}
		if (empty($hash)) {
			return "missing hash";
		}
		$clock_skew = $this->option("authentication_clock_skew", 10); // 10 seconds
		$delta = abs($time - $now);
		if ($delta > $clock_skew) {
			return "clock skew: ($delta = abs($time - $now)) > $clock_skew";
		}
		$hash_check = md5($time . "|" . $this->key());
		if ($hash !== $hash_check) {
			return "hash check failed $hash !== $hash_check";
		}
		return true;
	}

	/**
	 *
	 * @param string $action
	 */
	public function server_actions($action) {
		$app = $this->application;
		$servers = $app->orm_registry(Server::class)
			->query_select()
			->what_object()
			->link(Server_Data::class, array(
			"alias" => "d",
		))
			->where("d.name", Module::class)
			->where("d.value", serialize(1));
		$iterator = $servers->orm_iterator();
		$results = array();
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
	public function server_action(Server $server, $action) {
		$app = $this->application;
		$webapp = $app->webapp_module();
		$client = new Net_HTTP_Client($app);
		/* @var $webapp Module */
		$result = array(
			'ip' => $server->ip4_internal,
		);

		try {
			list($time, $hash) = $webapp->generate_authentication();
			$url = URL::query_append("http://" . $server->ip4_internal . "/webapp/$action", array(
				Controller::QUERY_PARAM_TIME => $time,
				Controller::QUERY_PARAM_HASH => $hash,
			));
			$client->url($url);
			$result['time'] = $time;
			$result['time_string'] = Timestamp::factory($time, "UTC")->format($app->locale, Timestamp::FORMAT_JSON);
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
}
