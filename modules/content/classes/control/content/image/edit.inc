<?php
class Control_Content_Image_Edit extends Control_Object_Edit {
	function _widgets() {
		$spec = array();

		//		$f = widgets::control_textarea("Body", "Body", true, -1, -1);
		//		$f->set_option("rows", 25);
		//		$f->set_option("cols", 80);
		//		$spec[$f->column()] = $f;
		//
		//		$f = widgets::control_textarea("Summary", "Summary", true, -1, -1);
		//		$f->set_option("cols", 80);
		//		$spec[$f->column()] = $f;
		//
		//		$f = widgets::control_date("Released", "Released", true);
		//		$spec[$f->column()] = $f;


		$f = widgets::control_image("ImagePath", "Image", false, "/data/image/{ImagePath}");
		$f->set_option("dest_path", zesk::application_root() . "www/data/image/{ImagePath}");
		$f->set_option("is_relative", true);
		$f->set_option("ScaleWidth", 400);
		$f->set_option("ScaleHeight", 400);
		$spec[$f->column()] = $f;

		$f = widgets::control_textarea("Caption", "Caption", false);
		$spec[$f->column()] = $f;

		$clamp_values = array(
			"integer_minimum" => 20,
			"integer_maximum" => 1000
		);
		$f = widgets::control_integer("DisplayWidth", "Width", false);
		$f->set_option($clamp_values);
		$f->suffix("<p class=\"tiny\">Option width to scale this image. If blank, uses site default.</p>");
		$spec[$f->column()] = $f;

		$f = widgets::control_integer("DisplayHeight", "Height", false);
		$f->set_option($clamp_values);
		$f->suffix("<p class=\"tiny\">Option height to scale this image. If blank, uses site default.</p>");
		$spec[$f->column()] = $f;

		$f = widgets::control_integer("ThumbWidth", "Thumbnail Width", false);
		$f->set_option($clamp_values);
		$f->suffix("<p class=\"tiny\">Option width to scale this image when shown as a thumbnail. If blank, uses site default.</p>");
		$spec[$f->column()] = $f;

		$f = widgets::control_integer("ThumbHeight", "Thumbnail Height", false);
		$f->set_option($clamp_values);
		$f->suffix("<p class=\"tiny\">Option height to scale this image when shown as a thumbnail. If blank, uses site default.</p>");
		$spec[$f->column()] = $f;

		$f = widgets::view_date("Created", "Created", "");
		$spec[$f->column()] = $f;

		$f = widgets::view_date("Modified", "Modified", "");
		$spec[$f->column()] = $f;

		return $spec;
	}
}