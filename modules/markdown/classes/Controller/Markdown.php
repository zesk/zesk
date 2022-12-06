<?php
declare(strict_types=1);
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
	public function _action_default($action = null): mixed {
		$file = $this->route->option('file');
		$content = $this->route->option('content');
		if ($file) {
			$search_path = $this->route->optionIterable('search_path', [
				$this->application->path(), $this->application->zeskHome(),
			]);

			try {
				$found_file = File::findFirst($search_path, $file);
				$content = file_get_contents($found_file);
			} catch (Exception_NotFound) {
				$this->application->logger->error('Page not found "{file}" in {search_path}', compact('file', 'search_path'));
				$this->error_404();
				return null;
			}
		}
		$new_content = $this->callHookArguments('content_process', [
			$content,
		], null);
		if (is_string($new_content)) {
			$content = $new_content;
		}
		return $this->theme('markdown', [
			'content' => $content, 'process' => true,
		]);
	}
}
