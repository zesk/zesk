<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 *            Created on Tue Jul 15 16:02:41 EDT 2008
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class View_ORM extends View {
	public function format($set = null) {
		if ($set !== null) {
			return $this->setOption('format', $set);
		}
		return $this->option('format');
	}

	public function display_method($set = null, $set_args = []) {
		if ($set !== null) {
			$this->setOption('display_method', $set);
			return $this->setOption('display_method_arguments', $set_args);
		}
		return $this->option('display_method');
	}

	public function themeVariables(): array {
		return [
			'object' => $this->value(),
			'format' => $this->format,
			'display_method' => $this->option('display_method'),
			'display_method_arguments' => $this->optionArray('display_method_arguments'),
			'object_class' => $this->class,
			'hidden_input' => $this->hidden_input(),
			'class_object' => $this->application->class_orm($this->option('class', $this->class)),
		] + parent::themeVariables();
	}
}
