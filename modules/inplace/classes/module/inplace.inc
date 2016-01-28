<?php
class Module_InPlace extends Module {
	public static function enabled($set = null) {
		if ($set !== null) {
			$set = !!$set;
			zesk::set(__CLASS__ . "::enabled", $set);
			return $set;
		}
		return zesk::getb(__CLASS__ . "::enabled");
	}
	public function hook_head(Request $request, Response_HTML $response, Template $template) {
		if (self::enabled()) {
			$response->jquery();
			$response->cdn_css("/share/inplace/css/inplace.css", array(
				'share' => true
			));
			$response->cdn_javascript("/share/inplace/js/jquery.inplace.js", array(
				'share' => true
			));
		}
	}
}