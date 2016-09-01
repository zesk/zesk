<?php

/**
 * Save your filters for lists to a saved list of queries
 * 
 * This is INCOMPLETE
 * 
 * @author kent
 */
class Module_Filter_Save extends Module_JSLib implements Module_Interface_Routes {
	
	/**
	 * 
	 * @var array
	 */
	protected $classes = array(
		"Filter_State"
	);
	
	/**
	 *
	 * @var array
	 */
	protected $javascript_paths = array(
		'/share/filter_save/filter_save.js'
	);
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Module_Interface_Routes::hook_routes()
	 */
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
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Module::initialize()
	 */
	function initialize() {
		$this->application->hooks->add("Control_Filter_Selector::menu_content", array(
			$this,
			"menu_prefix"
		));
	}
	
	/**
	 * 
	 * @param Control_Filter_Selector $widget
	 */
	public function menu_prefix(Control_Filter_Selector $widget) {
		$request = $widget->request();
		$response = $widget->response();
	}
	public function filter_state_new(Request $request, Response_HTML $response) {
	}
	public function filter_state_delete(Request $request, Response_HTML $response, Filter_State $filter) {
	}
}
