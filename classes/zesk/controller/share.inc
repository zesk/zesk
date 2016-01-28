<?php
class Zesk_Controller_Share extends Controller {
	function _action_default($action = null) {
		$uri = str::unprefix($original_uri = $this->request->path(), "/");
		if ($this->application->development() && $uri === "share/debug") {
			$this->response->content = self::share_debug();
			return;
		}
		list(, $uri) = pair($uri, "/", null, $uri);
		$share_paths = zesk::share_path();
		$file = null;
		foreach ($share_paths as $name => $path) {
			if (empty($name) || begins($uri, "$name/")) {
				$file = path($path, str::unprefix($uri, "$name/"));
				if (!is_dir($file) && file_exists($file)) {
					break;
				}
				$file = null;
			}
		}
		if (!$file) {
			$this->error_404();
			return;
		}
		$mod = $this->request->header('If-Modified-Since');
		$fmod = filemtime($file);
		if ($mod && $fmod <= strtotime($mod)) {
			$this->response->status(Net_HTTP::Status_Not_Modified);
			$this->response->content_type(mime::from_filename($file));
			$this->response->content = "";
			if ($this->option_bool('build')) {
				$this->build($original_uri, $file);
			}
			return;
		}

		// $this->response->header("X-Debug", "Mod - " . strtotime($mod) . " FMod - " . $fmod);
		$request = $this->request;
		if ($request->get("_ver")) {
			// Versioned resources are timestamped, expire never
			$this->response->header_date("Expires", strtotime("+1 year"));
		} else {
			$this->response->header_date("Expires", strtotime("+1 hour"));
		}
		$this->response->file($file);
		if ($this->option_bool('build')) {
			$this->build($original_uri, $file);
		}
	}

	private function build($path, $file) {
		$target = path(zesk::document_root(), $path);
		dir::depend(dirname($target), 0775);
		$status = copy($file, $target);
		log::notice("Copied {file} to {target} - {status}", array(
			"file" => $file,
			"target" => $target,
			"status" => $status ? "true" : "false"
		));
	}
	private function share_debug() {
		$content = "";
		$content .= html::tag("h1", "Server") . html::tag("pre", php::dump($_SERVER));
		$content .= html::tag("h1", "Request headers") . html::tag('pre', php::dump($this->request->header()));
		$content .= html::tag("h1", "Shares") . html::tag('pre', php::dump(zesk::share_path()));
		return $content;
	}

	public static function realpath($path) {
		$path = explode("/", trim($path, '/'));
		array_shift($path);
		$share = array_shift($path);
		$shares = zesk::share_path();
		if (array_key_exists($share, $shares)) {
			return path($shares[$share], implode("/", $path));
		}
		return null;
	}

	public function hook_cache_clear() {
		log::debug('{class}::hook_cache_clear', array(
			'class' => __CLASS__
		));
		if ($this->option_bool('build')) {
			$share_dir = path(zesk::document_root(), 'share');
			if (is_dir($share_dir)) {
				log::notice('{class}::hook_cache_clear - deleting {share_dir}', array(
					'class' => __CLASS__,
					'share_dir' => $share_dir
				));
				dir::delete($share_dir);
			} else {
				log::notice('{class}::hook_cache_clear - would delete {share_dir} but it is not found', array(
					'class' => __CLASS__,
					'share_dir' => $share_dir
				));

			}
		}
	}
}
