<?php

class Module_RespondJS extends Module {
	public function hook_head(Request $request, Response_HTML $response, Template $template) {
		if ($request->user_agent_is('ie')) {
			$response->jquery();
			$response->cdn_javascript(array(
				"/share/respondjs/dest/respond.min.js"
			), array(
				'share' => true
			));
		}
	}

}
