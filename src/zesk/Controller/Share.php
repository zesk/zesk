<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk\Controller;

use zesk\Exception\DirectoryCreate;
use zesk\Exception\DirectoryNotFound;
use zesk\Exception\DirectoryPermission;
use zesk\Exception\FileNotFound;
use zesk\Exception\FilePermission;

use zesk\Controller;
use zesk\Directory;
use zesk\MIME;
use zesk\PHP;
use zesk\StringTools;
use zesk\Request;
use zesk\Response;
use zesk\Exception\KeyNotFound;
use zesk\HTTP;
use zesk\HTML;

/**
 * Main share controller
 *
 * @author kent
 * @see docs/share.md
 */
class Share extends Controller {
	/**
	 * Paths to search for shared content
	 *
	 * @var string[]
	 */
	private array $sharePath = [];

	/**
	 * Whether to build the share directory as files are requested.
	 *
	 * Configure your webserver to run the main script upon missing file and
	 * serve the file if it exists otherwise.
	 */
	public const OPTION_BUILD = 'build';

	public const SHARE_PREFIX_DEFAULT = 'share';

	/**
	 * Option to override default
	 *
	 * @var string
	 */
	public const OPTION_SHARE_PREFIX = 'share_prefix';

	protected array $argumentMethods = [
		'arguments_{METHOD}',
	];

	protected array $actionMethods = [
		'action_{METHOD}',
	];

	protected array $beforeMethods = [
	];

	protected array $afterMethods = [
	];

	/**
	 * @return void
	 * @throws DirectoryNotFound
	 */
	protected function initialize(): void {
		parent::initialize();
		// Share files for Controller_Share
		$this->sharePath = [];
		$this->addSharePath($this->defaultSharePath(), 'zesk');
	}

	/**
	 * Add the share path for this application - used to serve
	 * shared content via Controller_Share as well as populate automatically with files within the
	 * system.
	 *
	 * By default, it's /share/
	 *
	 * @param string $add
	 * @param string $name
	 * @return self
	 * @throws DirectoryNotFound
	 */
	final public function addSharePath(string $add, string $name): self {
		if (!is_dir($add)) {
			throw new DirectoryNotFound($add);
		}
		$this->sharePath[$name] = $add;
		return $this;
	}

	/**
	 * Retrieve the share path for this application, a mapping of prefixes to paths
	 *
	 * By default, it's /share/
	 *
	 * @return array
	 *
	 * for example, returns a value:
	 *
	 *  `[ "home" => "/publish/app/api/modules/home/share/" ]`
	 */
	final public function sharePath(): array {
		return $this->sharePath;
	}

	/**
	 *
	 * @return string
	 */
	private function defaultSharePath(): string {
		return $this->application->zeskHome('share');
	}

	/**
	 *
	 * @return string
	 */
	public function option_share_prefix(): string {
		return $this->optionString(self::OPTION_SHARE_PREFIX, self::SHARE_PREFIX_DEFAULT);
	}

	/**
	 * For all share directories, copy files into document root share path to be served directly.
	 *
	 * This could be used in say, a build step for an application.
	 *
	 * @throws DirectoryCreate
	 * @throws DirectoryNotFound
	 * @throws DirectoryPermission
	 * @throws FilePermission
	 */
	public function build_directory(): void {
		$app = $this->application;
		$sharePaths = $this->sharePath();
		$document_root = $app->documentRoot();
		foreach ($sharePaths as $name => $path) {
			$app->logger->info('Reviewing {name} => {path}', [
				'name' => $name,
				'path' => $path,
			]);
			$files = Directory::ls($path);
			foreach ($files as $file) {
				$base = basename($file);
				$source = Directory::path($path, $file);
				if (!str_starts_with($base, '.') && is_file($source)) {
					$target_file = Directory::path($document_root, $this->option_share_prefix(), $name, $file);
					Directory::depend(dirname($target_file), 0o777);
					if (!copy($source, $target_file)) {
						throw new FilePermission($target_file);
					}
					$app->logger->info("Copied $source to $target_file");
				}
			}
		}
	}

	/**
	 *
	 * @param string $path
	 * @return ?string
	 */
	public function pathToFile(string $path): ?string {
		$uri = StringTools::removePrefix($path, '/');
		$uri = StringTools::pair($uri, '/', '', $uri)[1];
		$sharePaths = $this->sharePath();
		foreach ($sharePaths as $name => $path) {
			if (empty($name) || str_starts_with($uri, "$name/")) {
				$file = Directory::path($path, StringTools::removePrefix($uri, "$name/"));
				if (!is_dir($file) && file_exists($file)) {
					return $file;
				}
			}
		}
		return null;
	}

	/**
	 * Invoked by $this->argumentsMethods.
	 *
	 * @param Request $request
	 * @param Response $response
	 * @return array
	 * @see self::action_GET()
	 * @see $this->argumentsMethods
	 */
	public function arguments_GET(Request $request, Response $response): array {
		return [$request, $response];
	}

	/**
	 *
	 * @see self::arguments_GET()
	 * @see Controller::_action_default()
	 * @see $this->actionsMethods
	 */
	public function action_GET(Request $request, Response $response): Response {
		$original_uri = $request->path();
		$uri = StringTools::removePrefix($original_uri, '/');
		if ($this->application->development() && $uri === 'share/debug') {
			$response->content = $this->share_debug($request, $response);
			return $response;
		}
		$path = $request->path();
		$file = $this->pathToFile($path);
		if (!$file) {
			return $this->error_404($response);
		}

		try {
			$mod = $request->header('If-Modified-Since');
		} catch (KeyNotFound) {
			$mod = null;
		}
		$fmod = filemtime($file);
		if ($mod && $fmod <= strtotime($mod)) {
			$response->setStatus(HTTP::STATUS_NOT_MODIFIED);

			try {
				$response->setContentType(MIME::fromExtension($file));
			} catch (KeyNotFound) {
				$this->application->logger->warning('No content type for {file}', ['file' => $file]);
			}
			$response->content = '';
			$this->_buildOption($original_uri, $file);
			return $response;
		}

		if ($request->get('_ver')) {
			// Versioned resources are timestamped, expire never
			$response->setHeaderDate('Expires', strtotime('+1 year'));
			$response->setHeaderDate('Expires', strtotime('+1 year'));
		} else {
			$response->setHeaderDate('Expires', strtotime('+1 hour'));
		}

		try {
			$response->raw()->setFile($file);
		} catch (FileNotFound) {
			return $this->error_404($response, $path);
		}
		$this->_buildOption($original_uri, $file);
		return $response;
	}

	/**
	 * @param string $original_uri
	 * @param string $file
	 * @return void
	 */
	private function _buildOption(string $original_uri, string $file): void {
		if ($this->optionBool(self::OPTION_BUILD)) {
			try {
				$this->build($original_uri, $file);
			} catch (DirectoryPermission|DirectoryCreate $e) {
				$this->application->logger->error($e);
			}
		}
	}

	/**
	 * Copy file to destination so web server serves it directly next time
	 *
	 * @param string $path
	 * @param string $file
	 * @throws DirectoryCreate
	 * @throws DirectoryPermission
	 */
	private function build(string $path, string $file): void {
		$target = Directory::path($this->application->documentRoot(), $path);
		Directory::depend(dirname($target), 0o775);
		$status = copy($file, $target);
		$this->application->logger->notice('Copied {file} to {target} - {status}', [
			'file' => $file,
			'target' => $target,
			'status' => $status ? 'true' : 'false',
		]);
	}

	/**
	 * Output debug information during development
	 */
	private function share_debug(Request $request, Response $response): string {
		$content = HTML::tag('h1', 'Server') . HTML::tag('pre', PHP::dump($_SERVER));
		$content .= HTML::tag('h1', 'Request headers') . HTML::tag('pre', PHP::dump($request->headers()));
		$content .= HTML::tag('h1', 'Response') . HTML::tag('pre', PHP::dump($response->toJSON()));
		$content .= HTML::tag('h1', 'Shares') . HTML::tag('pre', PHP::dump($this->sharePath()));
		return $content;
	}

	/**
	 *
	 * @param string $path
	 * @return string
	 * @throws KeyNotFound
	 */
	public function realpath(string $path): string {
		$path = explode('/', trim($path, '/'));
		array_shift($path);
		$share = array_shift($path);
		$shares = $this->sharePath();
		if (array_key_exists($share, $shares)) {
			return Directory::path($shares[$share], implode('/', $path));
		}

		throw new KeyNotFound($share);
	}

	/**
	 * Clear the share build path upon cache clear
	 */
	public function hook_cacheClear(): void {
		$logger = $this->application->logger;
		$logger->debug(__METHOD__);
		if (!$this->optionBool(self::OPTION_BUILD)) {
			return;
		}
		$share_dir = Directory::path($this->application->documentRoot(), $this->option_share_prefix());
		if (is_dir($share_dir)) {
			$logger->notice('{class}::hook_cache_clear - deleting {share_dir}', [
				'class' => __CLASS__,
				'share_dir' => $share_dir,
			]);

			try {
				Directory::delete($share_dir);
			} catch (DirectoryNotFound) {
				// pass
			} catch (FilePermission|DirectoryPermission $e) {
				$logger->error($e);
			}
		} else {
			$logger->notice('{class}::hook_cache_clear - would delete {share_dir} but it is not found', [
				'class' => __CLASS__,
				'share_dir' => $share_dir,
			]);
		}
	}
}
