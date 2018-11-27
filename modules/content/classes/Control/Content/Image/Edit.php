<?php
namespace zesk;

class Control_Edit_Content_Image extends Control_Edit {
    public function _widgets() {
        $spec = array();

        //		$f = $this->widget_factory("control_textarea")->names("Body", "Body", true, -1, -1);
        //		$f->set_option("rows", 25);
        //		$f->set_option("cols", 80);
        //		$spec[$f->column()] = $f;
        //
        //		$f = $this->widget_factory("control_textarea")->names("Summary", "Summary", true, -1, -1);
        //		$f->set_option("cols", 80);
        //		$spec[$f->column()] = $f;
        //
        //		$f = $this->widget_factory("control_date")->names("Released", "Released", true);
        //		$spec[$f->column()] = $f;

        $f = $this->widget_factory("control_image")->names("ImagePath", "Image", false, "/data/image/{ImagePath}");
        $f->set_option("dest_path", $this->application->path("www/data/image/{ImagePath}"));
        $f->set_option("is_relative", true);
        $f->set_option("ScaleWidth", 400);
        $f->set_option("ScaleHeight", 400);
        $spec[$f->column()] = $f;

        $f = $this->widget_factory("control_textarea")->names("Caption", "Caption", false);
        $spec[$f->column()] = $f;

        $clamp_values = array(
            "integer_minimum" => 20,
            "integer_maximum" => 1000,
        );
        $f = $this->widget_factory("control_integer")->names("DisplayWidth", "Width", false);
        $f->set_option($clamp_values);
        $f->suffix("<p class=\"tiny\">Option width to scale this image. If blank, uses site default.</p>");
        $spec[$f->column()] = $f;

        $f = $this->widget_factory("control_integer")->names("DisplayHeight", "Height", false);
        $f->set_option($clamp_values);
        $f->suffix("<p class=\"tiny\">Option height to scale this image. If blank, uses site default.</p>");
        $spec[$f->column()] = $f;

        $f = $this->widget_factory("control_integer")->names("ThumbWidth", "Thumbnail Width", false);
        $f->set_option($clamp_values);
        $f->suffix("<p class=\"tiny\">Option width to scale this image when shown as a thumbnail. If blank, uses site default.</p>");
        $spec[$f->column()] = $f;

        $f = $this->widget_factory("control_integer")->names("ThumbHeight", "Thumbnail Height", false);
        $f->set_option($clamp_values);
        $f->suffix("<p class=\"tiny\">Option height to scale this image when shown as a thumbnail. If blank, uses site default.</p>");
        $spec[$f->column()] = $f;

        $f = $this->widget_factory("view_date")->names("Created", "Created", "");
        $spec[$f->column()] = $f;

        $f = $this->widget_factory("view_date")->names("Modified", "Modified", "");
        $spec[$f->column()] = $f;

        return $spec;
    }
}
