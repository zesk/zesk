<?php
/**
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 *            Created on Tue Jul 15 16:02:41 EDT 2008
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class View_ORM extends View {
    public function format($set = null) {
        if ($set !== null) {
            return $this->set_option("format", $set);
        }
        return $this->option('format');
    }

    public function display_method($set = null, $set_args = array()) {
        if ($set !== null) {
            $this->set_option('display_method', $set);
            return $this->set_option('display_method_arguments', $set_args);
        }
        return $this->option('display_method');
    }

    public function theme_variables() {
        return array(
            'object' => $this->value(),
            'format' => $this->format,
            'display_method' => $this->option('display_method'),
            'display_method_arguments' => $this->option_array('display_method_arguments'),
            "object_class" => $this->class,
            "hidden_input" => $this->hidden_input(),
            "class_object" => $this->application->class_orm($this->option('class', $this->class)),
        ) + parent::theme_variables();
    }
}
