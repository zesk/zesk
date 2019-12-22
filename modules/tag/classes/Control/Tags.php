<?php
/**
 * @package zesk
 * @subpackage tag
 * @author kent
 * @copyright &copy; 2019 Market Acumen, Inc.
 */
namespace zesk\Tag;

use zesk\Control;
use zesk\Selection_Type;
use zesk\Selection_Item;
use zesk\HTML;
use zesk\Exception_Semantics;

/**
 * @author kent
 */
class Control_Tags extends Control {
	/**
	 *
	 * @var Selection_Type
	 */
	protected $selection_type = null;

	/**
	 * Getter/setter for selection type
	 *
	 * @param Selection_Type $set
	 * @return \zesk\Selection_Type|\zesk\ORM\Control_Tags
	 */
	public function selection_type(Selection_Type $set = null) {
		if ($set === null) {
			return $this->selection_type;
		}
		$this->selection_type = $set;
		return $this;
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Widget::render()
	 */
	public function render() {
		//	$debug[] = __CLASS__;
		$debug = [];
		return HTML::etag("pre", implode("<br />", $debug)) . parent::render();
	}

	/**
	 *
	 * @throws Exception_Semantics
	 * @return \zesk\Database_Query_Select
	 */
	protected function query_tags_used() {
		if (!$this->selection_type) {
			throw new Exception_Semantics("Need selection_type set");
		}
		if (!$this->orm_class()) {
			throw new Exception_Semantics("Need orm_class() set");
		}
		$class_orm = $this->class_orm();
		assert($class_orm instanceof Class_Tag);

		$member = $class_orm->foreign_column;

		$application = $this->application;
		$selection_item_table = $application->orm_registry(Selection_Item::class)->table();
		$tags_query = $application->orm_registry($this->orm_class())
			->query_select("main")
			->link(Label::class, [
			'alias' => 'label',
			'path' => 'tag_label',
		])
			->what('id', 'label.id')
			->what("*total", "COUNT(DISTINCT items.id)")
			->join("INNER JOIN $selection_item_table items ON items.id=main.$member")
			->where([
			"items.type" => $this->selection_type->id(),
		])
			->group_by([
			'label.id',
		]);
		return $tags_query;
	}

	/**
	 * Convert a list of labels and remove offending labels using a hook
	 *
	 * @param Iterable $labels
	 * @return \zesk\Tag\Label[]
	 */
	public function filter_labels($labels) {
		$labels = $this->call_hook_arguments("filter_labels", array(
			$labels,
		), $labels);
		$by_id = [];
		foreach ($labels as $label) {
			if ($label instanceof Label) {
				$by_id[$label->id()] = $label;
			}
		}
		uasort($by_id, function (Label $a, Label $b) {
			return strcasecmp($a->name, $b->name);
		});
		return $by_id;
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Widget::theme_variables()
	 */
	public function theme_variables() {
		$application = $this->application;

		$tags_query = $this->query_tags_used();

		$labels = $application->tag_module()->list_labels();
		$labels = $this->filter_labels($labels);

		return parent::theme_variables() + [
			'selection_type' => $this->selection_type,
			'tags_query' => strval($tags_query),
			'labels_used' => $tags_query->to_array("id", "total"),
			'labels' => $labels,
		];
	}

	public function submit() {
		if (!parent::submit()) {
			return false;
		}
		$this->selection_type();
		return true;
	}
}
