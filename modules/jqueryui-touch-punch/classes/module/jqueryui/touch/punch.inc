<?php

class Module_JqueryUI_Touch_Punch extends Module {

	public static function head(Request $request, Response_HTML $response) {
		if ($request->user_agent_is("ios") || $request->get_bool('ios')) {
			$response->cdn_javascript("/share/jqueryui-touch-punch/jquery.ui.touch-punch.min.js", array(
				"share" => true,
				"weight" => "last"
			));
		}
	}

}
