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
	protected $class = Content_Image::class;

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
		$extras = array();
		if ($this->has_option('user_link_path')) {
			$extras['path'] = $this->option('user_link_path');
		}
		$query->link(User::class, array(
			"alias" => "user_image",
		) + $extras)->where("user_image.id", $this->user());
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
