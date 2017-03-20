<?php
class Module_BootstrapX_Clickover extends zesk\Module {
	public static function head(zesk\Request $request, zesk\Response_Text_HTML $response) {
		$response->cdn_javascript("/share/bootstrapx-clickover/js/bootstrapx-clickover.js", array(
			"share" => 1
		));
	}
}
