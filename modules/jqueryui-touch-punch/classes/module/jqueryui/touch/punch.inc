<?php declare(strict_types=1);
class Module_JqueryUI_Touch_Punch extends zesk\Module {
	public static function head(zesk\Request $request, zesk\Response $response): void {
		if ($request->user_agent_is("ios") || $request->get_bool('ios')) {
			$response->javascript("/share/jqueryui-touch-punch/jquery.ui.touch-punch.min.js", [
				"share" => true,
				"weight" => "last",
			]);
		}
	}
}
