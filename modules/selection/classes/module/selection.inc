<?php
class Module_Selection extends Module {
	protected $classes = array(
		"Selection_Item",
		"Selection_Query",
		"Selection_Type"
	);
	public function hook_routes(Router $router) {
		$router->add_route('selection/{Selection_Type type}/list', array(
			'controller' => 'Selection_Item',
			'action' => 'list',
			'actions' => array(
				'list'
			),
			'arguments' => array(
				1
			)
		));
		$router->add_route('selection/{Selection_Type type}/delete/{id}', array(
			'controller' => 'Selection_Item',
			'action' => 'delete_id',
			'actions' => array(
				'delete'
			),
			'arguments' => array(
				1,
				3
			)
		));
	}
}