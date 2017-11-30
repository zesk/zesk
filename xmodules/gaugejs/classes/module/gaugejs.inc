<?php
class Module_GaugeJS extends zesk\Module {
	public static function head(zesk\Request $request, zesk\Response_Text_HTML $response) {
		$response->javascript("/share/gaugejs/gauge.min.js");
	}
}
