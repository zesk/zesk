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
class Controller_Markdown extends Controller_Template {
	function _action_default($action = null) {
		$file = $this->route->option('file');
		$content = $this->route->option('content');
		if ($file) {
			$search_path = $this->route->option_list('search_path', array(
				$this->application->application_root(),
				$this->application->zesk_root()
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
		$content = $this->call_hook_arguments('content_process', array(
			$content
		), $content);
		echo $this->theme('markdown', array(
			'content' => $content,
			'process' => true
		));
	}
}