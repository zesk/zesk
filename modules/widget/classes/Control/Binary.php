<?php
namespace zesk;

class Control_Binary extends Control_Filter_Query {
    protected function initialize() {
        if (count($this->query_options) === 0) {
            $query_column = $this->query_column();
            $this->query_options(array(
                0 => array(
                    'title' => __('No'),
                    'where' => array(
                        $query_column => 0,
                    ),
                    'condition' => __('{label} is no'),
                ),
                1 => array(
                    'title' => __('Yes'),
                    'where' => array(
                        $query_column => 1,
                    ),
                    'condition' => __('{label} is yes'),
                ),
            ));
        }
        parent::initialize();
    }
}
