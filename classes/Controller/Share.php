<?php
declare(strict_types=1);

/**
 *
 */

namespace zesk;

/**
 * Main share controller
 *
 * @author kent
 * @see docs/share.md
 */
class Controller_Share extends Controller {
	public const SHARE_PREFIX_DEFAULT = 'share';

	/**
	 * Option to override default
	 *
	 * @var string
	 */
	public const OPTION_SHARE_PREFIX = 'share_prefix';

	/**
	 *
	 * @return string
	 */
	public function option_share_prefix() {
		return $this->option(self::OPTION_SHARE_PREFIX, self::SHARE_PREFIX_DEFAULT);
	}

	/**
	 * For all share directories, copy files into document root share path to be served directly.
	 *
	 * This could be used in say, a build step for an application.
	 *
	 * @throws Exception_File_Permission
	 */
	public function build_directory(): void {
		$app = $this->application;
		$share_paths = $this->application->sharePath();
		$document_root = $app->documentRoot();
		foreach ($share_paths as $name => $path) {
			$app->logger->info('Reviewing {name} => {path}', [
				'name' => $name,
				'path' => $path,
			]);
			$files = Directory::ls($path);
			foreach ($files as $file) {
				$base = basename($file);
				$source = path($path, $file);
				if (substr($base, 0, 1) !== '.' && is_file($source)) {
					$target_file = path($document_root, $this->option_share_prefix(), $name, $file);
					Directory::depend(dirname($target_file), 0o777);
					if (!copy($source, $target_file)) {
						throw new Exception_File_Permission($target_file);
					}
					$app->logger->info("Copied $source to $target_file");
				}
			}
		}
	}

	/**
	 *
	 * @param unknown $path
	 * @return string
	 */
	public function path_to_file($path) {
		$uri = StringTools::removePrefix($path, '/');
		$uri = pair($uri, '/', '', $uri)[1];
		$share_paths = $this->application->sharePath();
		foreach ($share_paths as $name => $path) {
			if (empty($name) || begins($uri, "$name/")) {
				$file = path($path, StringTools::removePrefix($uri, "$name/"));
				if (!is_dir($file) && file_exists($file)) {
					return $file;
				}
			}
		}
		return null;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Controller::_action_default()
	 */
	public function _action_default($action = null): mixed {
		$uri = StringTools::removePrefix($original_uri = $this->request->path(), '/');
		if ($this->application->development() && $uri === 'share/debug') {
			$this->response->content = $this->share_debug();
			return null;
		}
		$file = $this->path_to_file($this->request->path());
		if (!$file) {
			$this->error_404();
			return null;
		}
		$mod = $this->request->header('If-Modified-Since');
		$fmod = filemtime($file);
		if ($mod && $fmod <= strtotime($mod)) {
			$this->response->status(Net_HTTP::STATUS_NOT_MODIFIED);
			$this->response->content_type(MIME::from_filename($file));
			$this->response->content = '';
			if ($this->optionBool('build')) {
				$this->build($original_uri, $file);
			}
			return null;
			$this->response->header('X-Debug', 'Mod - ' . strtotime($mod) . ' FMod - ' . strtotime($fmod));
		}

		$request = $this->request;
		if ($request->get('_ver')) {
			// Versioned resources are timestamped, expire never
			$this->response->header_date('Expires', strtotime('+1 year'));
		} else {
			$this->response->header_date('Expires', strtotime('+1 hour'));
		}
		$this->response->raw()->file($file);
		if ($this->optionBool('build')) {
			$this->build($original_uri, $file);
		}
		return null;
	}

	/**
	 * Copy file to destination so web server serves it directly next time
	 *
	 * @param string $path
	 * @param string $file
	 */
	private function build($path, $file): void {
		$target = path($this->application->documentRoot(), $path);
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
	private function share_debug() {
		$content = '';
		$content .= HTML::tag('h1', 'Server') . HTML::tag('pre', PHP::dump($_SERVER));
		$content .= HTML::tag('h1', 'Request headers') . HTML::tag('pre', PHP::dump($this->request->headers()));
		$content .= HTML::tag('h1', 'Shares') . HTML::tag('pre', PHP::dump($this->application->sharePath()));
		return $content;
	}

	/**
	 *
	 * @param string $path
	 * @return string
	 */
	public static function realpath(Application $application, $path) {
		$path = explode('/', trim($path, '/'));
		array_shift($path);
		$share = array_shift($path);
		$shares = $application->sharePath();
		if (array_key_exists($share, $shares)) {
			return path($shares[$share], implode('/', $path));
		}
		return null;
	}

	/**
	 * Clear the share build path upon cache clear
	 */
	public function hook_cacheClear(): void {
		$logger = $this->application->logger;
		/* @var $locale \zesk\Locale */
		$logger->debug(__METHOD__);
		if ($this->optionBool('build')) {
			$share_dir = path($this->application->documentRoot(), $this->option_share_prefix());
			if (is_dir($share_dir)) {
				$logger->notice('{class}::hook_cache_clear - deleting {share_dir}', [
					'class' => __CLASS__,
					'share_dir' => $share_dir,
				]);
				Directory::delete($share_dir);
			} else {
				$logger->notice('{class}::hook_cache_clear - would delete {share_dir} but it is not found', [
					'class' => __CLASS__,
					'share_dir' => $share_dir,
				]);
			}
		}
	}
}
