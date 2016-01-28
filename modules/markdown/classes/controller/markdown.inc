<?php

class Controller_Markdown extends Controller_Template {

	function _action_default($action = null) {
		$file = $this->route->option('file');
		$content = $this->route->option('content');
		if ($file) {
			$search_path = $this->route->option_list('search_path', array(
				zesk::application_root(),
				zesk::root()
			));
			$found_file = zesk::find_file($search_path, $file);
			if ($found_file) {
				$content = file_get_contents($found_file);
			} else {
				log::error("Page not found \"{file}\" in {search_path}", compact("file", "search_path"));
				$this->error_404();
				return;
			}
		}
		$content = $this->hook_array('content_process', array(
			$content
		), $content);
		echo theme('markdown', array(
			'content' => $content,
			'process' => true
		));
	}
}