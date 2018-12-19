<?php
class Module_RespondJS extends zesk\Module {
	public function hook_head(zesk\Request $request, zesk\Response $response, zesk\Template $template) {
		if ($request->user_agent_is('ie')) {
			$response->jquery();
			$response->javascript(array(
				"/share/respondjs/dest/respond.min.js",
			), array(
				'share' => true,
			));
		}
	}
}
