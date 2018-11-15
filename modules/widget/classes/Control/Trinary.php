<?php
namespace zesk;

class Control_Trinary extends Control_Select {
    protected function initialize() {
        parent::initialize();
        $this->control_options(array(
            'null' => __('Unanswered'),
            0 => __('No'),
            1 => __('Yes'),
        ));
    }

    protected function hook_query(Database_Query_Select $query) {
        $val = $this->value();
        $column = $this->query_column();
        if ($val === "null") {
            $query->condition(__("have not answered {label}", array(
                "label" => $this->label,
            )), $this->query_condition_key());
            $query->where($column, null);
        } elseif (is_numeric($val)) {
            $query->condition(__("answered {value} for {label}", array(
                "label" => $this->label,
                "value" => $val ? __("yes") : __("no"),
            )), $this->query_condition_key());
            $query->where($column, $val);
        }
    }
}
