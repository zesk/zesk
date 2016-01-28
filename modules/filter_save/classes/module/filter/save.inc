<?php
class Module_Filter_Save extends Module_JSLib implements Module_Interface_Routes {
	protected $classes = array(
		"Filter_State"
	);
	protected $javascript_paths = array(
		'/share/filter_save/filter_save.js'
	);
	public function hook_routes(Router $router) {
		$router->add_route('filter_state/new', array(
			"method" => array(
				$this,
				"filter_state_new"
			),
			"arguments" => array(
				"{request}",
				"{response}"
			)
		));
		$router->add_route('filter_state/{Filter_State filter}/delete', array(
			"method" => array(
				$this,
				"filter_state_delete"
			),
			"arguments" => array(
				"{request}",
				"{response}",
				1
			)
		));
	}
	function initialize() {
		zesk::add_hook("Control_Filter_Selector::menu_content", array(
			$this,
			"menu_prefix"
		));
	}
	public function menu_prefix(Control_Filter_Selector $widget) {
		$request = $widget->request();
		$response = $widget->response();
	}
	public function filter_state_new(Request $request, Response_HTML $response) {
	}
	public function filter_state_delete(Request $request, Response_HTML $response, Filter_State $filter) {
	}
}
