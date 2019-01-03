<?php
/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Control_Picker_Content_Image extends Control_Picker {
	/**
	 *
	 * @var string
	 */
	protected $class = "zesk\Content_Image";

	/**
	 *
	 * @var array
	 */
	protected $search_columns = array(
		'X.title',
		'X.description',
		'X.path',
	);

	/**
	 *
	 * {@inheritDoc}
	 * @see Control_Picker::hook_query()
	 */
	public function hook_query(Database_Query_Select $query) {
		parent::hook_query($query);

		$query->link("User", array(
			"alias" => "user_image",
		))->where("user_image.id", $this->user());
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see Control_Picker::theme_variables()
	 */
	public function theme_variables() {
		return array(
			'label_search' => $this->application->locale->__('Search uploaded images'),
		) + parent::theme_variables();
	}
}
