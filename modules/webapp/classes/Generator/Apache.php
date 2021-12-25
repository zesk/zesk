<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage webapp
 * @author kent
 * @copyright &copy; 2019 Market Acumen, Inc.
 */
namespace zesk\WebApp;

use zesk\Directory;
use zesk\Exception_Semantics;
use zesk\ArrayTools;
use zesk\Text;

class Generator_Apache extends Generator {
	/**
	 *
	 * @var Module
	 */
	private $webapp = null;

	/**
	 *
	 * @var Instance
	 */
	private $instance = null;

	/**
	 *
	 * @var array
	 */
	private $site_names = [];

	/**
	 *
	 * @var string
	 */
	private $vhost_path = null;

	/**
	 *
	 * @var array
	 */
	private $changed = null;

	/**
	 * Returns an array of errors
	 *
	 * @param array $data
	 * @return array
	 */
	public function validate(array $data) {
		return [];
	}

	/**
	 * @return self
	 */
	public function start() {
		$this->instance = null;
		$this->site_names = [];
		$this->webapp = $this->application->webapp_module();
		$this->changed = [];
		$this->vhost_path = $this->webapp->webapp_data_path("vhosts/");
		Directory::depend($this->vhost_path, 0o755);
		if ($this->option_bool("clean")) {
			Directory::delete_contents($this->vhost_path);
		}
		return $this;
	}

	/**
	 *
	 * @param string $contents
	 * @return string
	 */
	protected function hook_file_compare_preprocess($contents) {
		return Text::remove_line_comments($contents, "#", false);
	}

	/**
	 * Generate a web application instance
	 *
	 * {@inheritDoc}
	 * @see \zesk\WebApp\Generator::instance()
	 */
	public function instance(Instance $instance) {
		$this->instance = $instance;
		return $this;
	}

	/**
	 * Render a site as part of an instance
	 *
	 * {@inheritDoc}
	 * @see \zesk\WebApp\Generator::site()
	 */
	public function site(Site $site) {
		if (!$this->instance) {
			throw new Exception_Semantics("Must call instance before calling site");
		}
		$domains = $site->domains();
		$cluster_names = [];
		$site_names = [];
		foreach ($domains as $domain) {
			/* @var $domain Domain */
			if ($domain->type === Cluster::class) {
				$cluster_names[] = $domain->name;
			} else {
				$site_names[] = $domain->name;
			}
		}
		$app = $site->application;
		$data = to_array($site->data);
		$contents = $app->theme("webapp/generator/apache/site", [
			"generator" => $this,
			"site" => $site,
			"hostnames" => array_merge($cluster_names, $site_names),
			"instance" => $this->instance,
			"source" => $this->instance->path,
			"webappbin" => $app->webapp_module()->binary(),
		] + $site->members() + $data + $this->template_defaults() + $this->option());
		$filename = $this->instance->code . "-" . $site->code . ".conf";
		$fullpath = path($this->vhost_path, $filename);
		$this->changed += $this->replace_file($fullpath, $contents);
		if (!empty($contents)) {
			$this->site_names[] = $fullpath;
		}
		return $this;
	}

	/**
	 *
	 * @return mixed|NULL
	 */
	public function log_path() {
		$path = $this->application->configuration->LOG_PATH;
		if (!$path) {
			$path = $this->application->option("log_path");
		}
		return $path;
	}

	protected function replace_file($file, $contents) {
		$map = [];
		$map['${LOG_PATH}'] = $this->log_path();
		$map = ArrayTools::clean($map, [
			"",
			false,
			null,
		]);
		return parent::replace_file($file, strtr($contents, $map));
	}

	/**
	 * @return self
	 */
	public function finish() {
		$fullpath = path($this->vhost_path, "@webapp.conf");
		$contents = $this->application->theme("webapp/generator/apache/all", [
			"generator" => $this,
			"includes" => $this->site_names,
		]);
		$this->changed += $this->replace_file($fullpath, $contents);
		return $this;
	}

	/**
	 * Did something change?
	 *
	 * {@inheritDoc}
	 * @see \zesk\WebApp\Generator::changed()
	 */
	public function changed() {
		return ArrayTools::kunprefix($this->changed, $this->vhost_path);
	}

	/**
	 * Template default values
	 * @return array
	 */
	public function template_defaults() {
		return [
			"node_application" => false,
			"no_webapp" => false,
			"hostnames" => [],
			"indexes" => [
				"index.php",
				"index.html",
			],
			"port" => 80,
		];
	}

	public function deploy(array $options = []) {
		$log_path = $this->log_path();
		if (!empty($log_path)) {
			Directory::depend(path($log_path, "httpd"));
		}
		return $this;
	}
}
