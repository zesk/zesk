<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage widgets
 * @author kent <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 * Created on Tue,Sep 22, 09 at 1:16 PM
 */
namespace zesk;

class View_Section extends View {
	protected array $options = [
		'nav' => true,
	];

	public function validate(): bool {
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
		$this->setOption('column_count_label', $label);
		$this->setOption('column_count_widget', $widget);
		return $this;
	}

	public function controller() {
		$content = $this->render();
		if ($this->preferJSON()) {
			$response = $this->response();
			$response->json()->data([
				'status' => true,
				'content' => $content,
				'title' => $response->html()->title(),
			]);
			return;
		}
		return $content;
	}
}
