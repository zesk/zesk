<?php
use zest\Project;

/**
 * 
 * @author kent
 *
 */
class Control_List_Zest_Test extends Control_List {
	
	/**
	 * 
	 * @var string
	 */
	protected $class = "zest\Test";
	/**
	 * 
	 * @var zest\Project
	 */
	protected $project = null;
	
	/**
	 * 
	 * @var string
	 */
	protected $theme_widgets = "object/zestm/test/row";
	
	/**
	 * 
	 * @param zest\Project $project
	 * @return Control_List_Zest_Test|\zest\Project
	 */
	public function zest_project(zest\Project $project = null) {
		if ($project) {
			$this->project = $project;
			return $this;
		}
		return $this->project;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Control::hook_query()
	 */
	protected function hook_query(Database_Query_Select $query) {
		if ($this->project) {
			$query->where("project", $this->project);
		}
	}
	
	/**
	 * 
	 * @return Widget[]
	 */
	protected function hook_row_widgets() {
		$ww = array();
		
		$w = $this->widget_factory("View_Text")->names("relpath", __("Test"));
		$ww[] = $w;
		
		return $ww;
	}
}