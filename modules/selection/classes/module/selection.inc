<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 *
 */
class Module_Selection extends Module {
	protected array $model_classes = [
		"zesk\\Selection_Item",
		"zesk\\Selection_Query",
		"zesk\\Selection_Type",
	];

	public function hook_routes(Router $router): void {
		$router->add_route('selection/{zesk\\Selection_Type type}/list', [
			'controller' => 'zesk\\Controller_Selection_Item',
			'action' => 'list',
			'actions' => [
				'list',
			],
			'arguments' => [
				1,
			],
		]);
		$router->add_route('selection/{zesk\\Selection_Type type}/delete/{id}', [
			'controller' => 'zesk\\Controller_Selection_Item',
			'action' => 'delete_id',
			'actions' => [
				'delete',
			],
			'arguments' => [
				1,
				3,
			],
		]);
	}
}
