<?php

class Module_GaugeJS extends Module {
	public static function head(Request $request, Response_HTML $response) {
		$response->cdn_javascript("/share/gaugejs/gauge.min.js");
	}
}
