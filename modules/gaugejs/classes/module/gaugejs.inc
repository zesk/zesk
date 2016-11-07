<?php

class Module_GaugeJS extends Module {
	public static function head(zesk\Request $request, zesk\Response_Text_HTML $response) {
		$response->cdn_javascript("/share/gaugejs/gauge.min.js");
	}
}
