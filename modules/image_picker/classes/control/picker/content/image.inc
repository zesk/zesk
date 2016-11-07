<?php
/**
 * 
 */
use zesk\Database_Query_Select;

/**
 * 
 * @author kent
 *
 */
class Control_Picker_Content_Image extends Control_Picker {
	protected $class = "Content_Image";
	protected $search_columns = array(
		'X.title',
		'X.description',
		'X.path'
	);
	function hook_query(zesk\Database_Query_Select $query) {
		parent::hook_query($query);
		
		$query->link("User", array(
			"alias" => "user_image"
		))->where("user_image.id", $this->user());
	}
	public function theme_variables() {
		return array(
			'label_search' => __('Search uploaded images')
		) + parent::theme_variables();
	}
}
