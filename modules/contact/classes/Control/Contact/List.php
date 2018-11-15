<?php
namespace zesk;

class Control_Contact_List extends Control_List {
    protected $class = "zesk\\Contact";

    public function hook_widgets() {
        $widgets = array();
        
        $widgets[] = $this->widget_factory(View_Text::class)->names("name");
        
        return $widgets;
    }
}
