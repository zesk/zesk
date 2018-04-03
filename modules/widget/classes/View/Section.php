<?php
/**
 * @package zesk
 * @subpackage widgets
 * @author kent <kent@marketacumen.com>
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 * Created on Tue,Sep 22, 09 at 1:16 PM
 */
namespace zesk;

class View_Section extends View {
	protected $options = array(
		'nav' => true
	);
	public function validate() {
		$validate = true;
		foreach ($this->children as $child) {
			if (!$child->validate()) {
				$validate = false;
			}
		}
		return $validate;
	}
	public function set_columns($label, $widget = null) {
		$label = clamp(1, intval($label), 12);
		if ($widget === null) {
			$widget = $label === 12 ? 12 : 12 - $label;
		}
		$this->set_option('column_count_label', $label);
		$this->set_option('column_count_widget', $widget);
		return $this;
	}
	public function controller() {
		$content = $this->render();
		if ($this->prefer_json()) {
			$response = $this->response();
			$response->json()->data(array(
				'status' => true,
				'content' => $content,
				'title' => $response->html()->title()
			));
			return;
		}
		return $content;
	}
}
