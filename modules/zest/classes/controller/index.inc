<?php
class Controller_Index extends Controller_Template {
	public function _action_default($action = null) {
		$this->action_list();
	}
	public function action_list(zest\Project $project = null) {
		if ($project === null) {
			$project = new zest\Project(array(
				"path" => zesk()->paths->zesk(),
			));
			$project->register();
		}
		
		$tests = $project->tests();
		
		$this->control($this->widget_factory("Control_List_Zest_Test")->zest_project($project));
	}
}
