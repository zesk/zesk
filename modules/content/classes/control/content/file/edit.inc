<?php
class Control_Content_File_Edit extends Control_Object_Edit {
	function listWidgetList() {
		$spec = array();
		
		$f = widgets::view_object("Parent", "Group", _G('table_prefix') . "FileGroup", "{Name}");
		$spec[$f->column()] = $f;
		
		$f = widgets::view_text("Name", "Name");
		$spec[$f->column()] = $f;
		
		$f = widgets::view_text("FileName", "File");
		$spec[$f->column()] = $f;
		
		$actions = array(
			array(
				"href" => "/manage/file/download.php?ID={ID}&ref=/manage/file/list.php", 
				"src" => "/share/images/actions/download.gif", 
				"text" => "Download \"{Name}\""
			)
		);
		$f = widgets::actions();
		$f->set_option("Actions", $actions);
		$spec["actions"] = $f;
		
		return $spec;
	}
	function newWidgetList() {
		$spec = $this->editWidgetList();
		unset($spec["Created"]);
		unset($spec["Modified"]);
		return $spec;
	}
	function _widgets(Model $model) {
		$spec = array();
		
		$f = widgets::view_text("MIMEType", "MIME Type", true);
		$spec[$f->column()] = $f;
		
		$f = widgets::control_text("Name", "Name", true, 1, 128);
		$spec[$f->column()] = $f;
		
		$f = widgets::control_file("FileName", "File", true);
		$f->set_option("filecolumn", "Path");
		$f->set_option("dest_path", zesk::site_root() . "data/files/{FileName}");
		$spec[$f->column()] = $f;
		
		$f = widgets::control_textarea("Description", "Description");
		$spec[$f->column()] = $f;
		
		$f = User::controlWidget("User", "Owner", true);
		$spec[$f->column()] = $f;
		
		return $spec;
	}
}
