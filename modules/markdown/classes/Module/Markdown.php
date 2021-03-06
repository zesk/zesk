<?php
namespace zesk;

class Module_Markdown extends Module implements Interface_Module_Routes {
	public static function request(Request $request) {
		$input = $request->get('markdown');
		$tab_size = $request->geti('tab_size', Markdown::default_tab_size);
		return Markdown::filter($input, array(
			'tab_size' => $tab_size,
		));
	}

	public function hook_routes(Router $router) {
		$options = array(
			'method' => __CLASS__ . '::request',
			'arguments' => '{request}',
			'weight' => -10,
			'page template' => null,
		);
		$router->add_route('markdown', $options);
	}
}
