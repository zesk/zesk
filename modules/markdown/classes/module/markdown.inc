<?php

class Module_Markdown extends Module {

	public static function request(Request $request) {
		$input = $request->get('markdown');
		$tab_size = $request->geti('tab_size', Markdown::default_tab_size);
		return Markdown::filter($input, array(
			'tab_size' => $tab_size
		));
	}

	public function hook_routes(Router $router) {
		$options = array(
			'method' => 'Module_Markdown::request',
			'arguments' => '{request}',
			'weight' => -10,
			'page template' => null
		);
		$router->add_route('markdown', $options);
	}

}