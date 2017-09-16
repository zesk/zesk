<?php
namespace zesk;

class Controller_AJAX extends Controller {
	public function before() {
		parent::json(true);
	}
	public function _action_default($action = null) {
		$path = explode("/", ltrim($this->request->path(), '/'));
		array_shift($path);
		$new_path = implode("/", $path);
		$this->request->path($new_path);
		$router = $this->router;
		$router->match($this->request);
		$router->execute($this->application);
		return $this->json($this->response->to_json() + array(
			"content" => $this->response->content
		));
	}
}
