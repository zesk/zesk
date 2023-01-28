<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage widgets
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 * Created on Tue Jul 15 16:00:34 EDT 2008
 */
namespace zesk;

class View_Select extends Control_Optionss {
	public function render(): string {
		if ($this->optionBool('hidden_input')) {
			$this->addWrap(null, null, null, HTML::hidden($this->name(), $this->value()));
		}
		$options = $this->control_options;
		$value = $this->value();
		$value = $options["$value"] ?? $this->empty_string();
		if (is_array($value)) {
			$value = $value['label'] ?? $this->empty_string();
		}
		return $this->render_finish($value);
	}
}
