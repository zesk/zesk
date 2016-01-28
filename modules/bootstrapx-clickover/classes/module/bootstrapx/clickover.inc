<?php
class Module_BootstrapX_Clickover extends Module {
	public static function head(Request $request, Response_HTML $response) {
		$response->cdn_javascript("/share/bootstrapx-clickover/js/bootstrapx-clickover.js", array(
			"share" => 1
		));
	}
}
