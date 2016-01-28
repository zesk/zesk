<?php
class Controller_Cache extends Controller {
	protected function request_to_file($contents) {
		$file = $this->request->path();
		if (!file::path_check($file)) {
			$message = "User accessed {file} which contains suspicious path components while trying to write {contents_size} bytes.";
			$args = array(
				"file" => $file,
				"contents_size" => strlen($contents)
			);
			log::error($message, $args);
			zesk::hook("security", $message, $args);
			return null;
		}
		$docroot = zesk::document_root();
		$cache_file = dir::undot(path($docroot, $file));
		if (!begins($cache_file, $docroot)) {
			zesk::hook("security", "User cache file \"{cache_file}\" does not match doc root \"{docroot}\"", array(
				"cache_file" => $cache_file,
				"docroot" => $docroot
			));
			return null;
		}
		if ($this->request->get('nocache') === '123123') {
			$this->response->content_type = mime::from_filename($cache_file);
			$this->response->header('Content-Length', strlen($contents));
			$this->response->content = $contents;
			return;
		}
		dir::depend(dirname($cache_file), $this->option("cache_directory_mode", 0775));
		file_put_contents($cache_file, $contents);
		$this->response->file($cache_file);
	}
}
