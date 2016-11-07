<?php
/**
 * @deprecated 2015-01
 * @author kent
 *
 */
class Control_Content_Link_List extends Control_Object_List {

	function listWidgetList() {
		$spec = array();

		$f = widgets::view_link("Name", "Name", "{Name}");
		$f->set_option("html", true);
		$spec[$f->column()] = $f;

		//		$f = widgets::view_text("Body", "Body", 200);
		//		$spec[$f->column()] = $f;


		$f = widgets::view_text("ClickCount", "Clicked");
		$f->set_option("align", "center");
		$f->set_option("empty_string", "<em>Never</em>");
		$spec[$f->column()] = $f;

		if ($this->option_bool("ordering_enabled")) {
			$f = widgets::control_order("OrderIndex", "Order", $this->table());
			$f->set_option("where", array(
				"Parent" => $this->request->geti("Parent")
			));
			$spec[$f->column()] = $f;
		}

		$spec["actions"] = widgets::actions();

		return $spec;
	}

	function newWidgetList() {
		$spec = $this->editWidgetList();
		unset($spec["Created"]);
		unset($spec["Modified"]);
		return $spec;
	}

	function editWidgetList() {
		HTML::jquery();
		HTML::cdn_javascript("/share/zesk/js/link.js");

		$spec = array();

		$f = widgets::control_url("URL", "URL", true);
		$f->set_option("class", "large");
		$f->set_option("id", "Link_URL");
		$f->set_option("onblur", "url_update_title('Link_URL', 'Link_Name', 'Link_Name_Progress', 'Link_Name_Error', true)");
		$f->suffix(HTML::tag("input", array(
			"type" => "button",
			"class" => "link-update-title",
			"onclick" => "url_update_title('Link_URL', 'Link_Name', 'Link_Name_Progress')",
			"value" => "Update Title from URL"
		), null) . HTML::tag('div', array(
			'id' => 'Link_Name_Error'
		), ""));
		$spec[$f->column()] = $f;

		$f = widgets::control_text("Name", "Title", true, 1, 255);
		$f->set_option("id", "Link_Name");
		$f->suffix(HTML::tag("div", array(
			"id" => "Link_Name_Progress"
		), ""));
		$spec[$f->column()] = $f;

		$f = widgets::control_richtext("Body", "Description", false);
		$f->set_option("rows", 8);
		$f->set_option("cols", 80);
		$spec[$f->column()] = $f;

		$f = widgets::control_image("ImagePath", "Image", false, "/data/link/{ImagePath}");
		$f->set_option("dest_path", _G('Zesk::site_root_path') . "www/data/link/{ImagePath}");
		$f->set_option("is_relative", true);
		$f->set_option("ScaleWidth", 400);
		$f->set_option("ScaleHeight", 400);
		$spec[$f->column()] = $f;

		$f = widgets::control_select_object("Parent", "Group", _G('table_prefix') . 'LinkGroup', true);
		$f->set_option("textcolumn", "Name,CodeName");
		$f->set_option("format", "{Name} ({CodeName})");
		$f->set_option("", "");
		$spec[$f->column()] = $f;

		$f = widgets::view_date("Created", "Created", "");
		$spec[$f->column()] = $f;

		$f = widgets::view_date("Modified", "Modified", "");
		$spec[$f->column()] = $f;

		return $spec;
	}
}
