<?php
class Module_Modal_URL extends zesk\Module_JSLib {
	/**
	 *
	 * @var array
	 */
	protected $jquery_ready = array(
		'$.modal_url_ready();',
	);

	/**
	 * (non-PHPdoc)
	 *
	 * @see zesk\Module_JSLib::hook_head()
	 */
	public function hook_head(zesk\Request $request, zesk\Response $response, zesk\Template $template) {
		$response->jquery();
		$response->css("/share/modal_url/css/modal_url.css", array(
			'share' => true,
		));
		$response->javascript("/share/modal_url/js/jquery.modal_url.js", array(
			'share' => true,
		));
		$response->javascript_inline($this->application->theme("modal/url", array(
			"id" => "modal_url-modal",
			'dismiss_button' => $this->option_bool('dismiss_button', true),
		)), array(
			"type" => "text/x-template",
			"id" => "modal_url-template",
		));
	}
}
