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
class Controller_Markdown extends Controller_Theme {
	public function _action_default($action = null) {
		$file = $this->route->option('file');
		$content = $this->route->option('content');
		if ($file) {
			$search_path = $this->route->option_list('search_path', array(
				$this->application->path(),
				$this->application->zesk_root(),
			));
			$found_file = File::find_first($search_path, $file);
			if ($found_file) {
				$content = file_get_contents($found_file);
			} else {
				$this->application->logger->error("Page not found \"{file}\" in {search_path}", compact("file", "search_path"));
				$this->error_404();
				return;
			}
		}
		$new_content = $this->call_hook_arguments('content_process', array(
			$content,
		), null);
		if (is_string($new_content)) {
			$content = $new_content;
		}
		return $this->theme('markdown', array(
			'content' => $content,
			'process' => true,
		));
	}
}
