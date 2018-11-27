<?php
namespace zesk;

class Control_FieldSet extends Control_Widgets {
    protected $options = array(
        "nolabel" => true,
    );

    public function initialize() {
        $this->prefix .= HTML::tag('legend', $this->label);
        $this->wrap('fieldset', $this->attributes(array(
            "class" => "control-fieldset",
            "id" => $this->id,
        ), "fieldset"));
        parent::initialize();
    }

    // 	public function render() {
    // 		echo get_class($this) . '<br />' . HTML::tag('h2', $this->name()) . _backtrace();
    // 		return parent::render();
    // 	}
}
