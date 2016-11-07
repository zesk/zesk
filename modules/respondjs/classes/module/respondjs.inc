<?php

class Module_RespondJS extends Module {
	public function hook_head(zesk\Request $request, zesk\Response_Text_HTML $response, zesk\Template $template) {
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
