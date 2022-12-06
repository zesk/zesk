<?php declare(strict_types=1);
class Module_Modal_URL extends zesk\Module_JSLib {
	/**
	 *
	 * @var array
	 */
	protected $jquery_ready = [
		'$.modal_url_ready();',
	];

	/**
	 * (non-PHPdoc)
	 *
	 * @see zesk\Module_JSLib::hook_head()
	 */
	public function hook_head(zesk\Request $request, zesk\Response $response, zesk\Template $template): void {
		$response->jquery();
		$response->css('/share/modal_url/css/modal_url.css', [
			'share' => true,
		]);
		$response->javascript('/share/modal_url/js/jquery.modal_url.js', [
			'share' => true,
		]);
		$response->javascript_inline($this->application->theme('modal/url', [
			'id' => 'modal_url-modal',
			'dismiss_button' => $this->optionBool('dismiss_button', true),
		]), [
			'type' => 'text/x-template',
			'id' => 'modal_url-template',
		]);
	}
}
