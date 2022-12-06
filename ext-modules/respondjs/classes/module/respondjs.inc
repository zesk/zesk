<?php declare(strict_types=1);
class Module_RespondJS extends zesk\Module {
	public function hook_head(zesk\Request $request, zesk\Response $response, zesk\Template $template): void {
		if ($request->user_agent_is('ie')) {
			$response->jquery();
			$response->javascript([
				'/share/respondjs/dest/respond.min.js',
			], [
				'share' => true,
			]);
		}
	}
}
