<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage tag
 * @author kent
 * @copyright &copy; 2020 Market Acumen, Inc.
 */
namespace zesk\Tag;

/**
 * @author kent
 */
class Control_Label extends \zesk\Control_Select_ORM {
	protected $class = Label::class;

	protected $options = [
		'text_column' => 'name',
		'id_column' => 'id',
	];

	protected function initialize(): void {
		if (!$this->has_option('noname')) {
			$this->noname($this->application->locale->__(self::class . ':=No tag'));
		}
		parent::initialize();
	}

	protected function hook_options() {
		$module = $this->application->tag_module();
		/* @var $module Module */
		$labels = $module->list_labels();
		$options = [];
		foreach ($labels as $label) {
			$options[$label->id()] = $label->name;
		}
		if ($this->has_option("exclude_items")) {
			foreach ($this->option_list("exclude_items") as $id) {
				unset($options[$id]);
			}
		}
		return $options;
	}
}
