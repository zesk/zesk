<?php
namespace zesk;

class View_Theme extends View {
    public function initialize() {
        parent::initialize();
        $this->theme = $this->option('theme');
    }
}
