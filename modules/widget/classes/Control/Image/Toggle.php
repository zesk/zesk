<?php

/**
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * Created on Tue Jul 15 16:38:32 EDT 2008
 */
namespace zesk;

class Control_Image_Toggle extends Control {
    public function render() {
        $locale = $this->locale();
        $value = $this->value();
        $on_value = $this->option("true_value", true);

        $attrs = $this->options_include("width;height;border;hspace;vspace");
        $response = $this->response();
        $id = "toggle_image_" . $response->id_counter();
        $js_object = $this->option(array(
            "true_src" => null,
            "true_alt" => $locale->__("Click here to enable"),
            "false_src" => null,
            "false_alt" => $locale->__("Click here to disable"),
            "notify_url" => null,
            "true_value" => 'true',
            'false_value' => 'false',
        ));
        $prefix = ($value === $on_value) ? "true" : "false";

        $div_attrs = $this->option(array(
            "class" => "ControlToggleImage",
            "style" => null,
        ));
        $div_attrs['id'] = $id;
        $content = HTML::tag("div", $div_attrs, HTML::img($this->application, avalue($js_object, $prefix . "_src"), avalue($js_object, $prefix . "_alt"), $attrs));
        $response->html()->jquery('$(\'#' . $id . '\').toggleImage(' . json_encode($js_object) . ');');

        return $content;
    }
}
