<?php
/**
 *
 */
namespace zesk;

/**
 *
 */
class Module_Selection extends Module {
	protected $model_classes = array(
		"zesk\\Selection_Item",
		"zesk\\Selection_Query",
		"zesk\\Selection_Type",
	);

	public function hook_routes(Router $router) {
		$router->add_route('selection/{zesk\\Selection_Type type}/list', array(
			'controller' => 'zesk\\Controller_Selection_Item',
			'action' => 'list',
			'actions' => array(
				'list',
			),
			'arguments' => array(
				1,
			),
		));
		$router->add_route('selection/{zesk\\Selection_Type type}/delete/{id}', array(
			'controller' => 'zesk\\Controller_Selection_Item',
			'action' => 'delete_id',
			'actions' => array(
				'delete',
			),
			'arguments' => array(
				1,
				3,
			),
		));
	}
}
