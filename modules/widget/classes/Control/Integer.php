<?php
namespace zesk;

class Control_Integer extends Control_Text {
    protected $options = array(
        'validate' => 'integer',
    );
}
