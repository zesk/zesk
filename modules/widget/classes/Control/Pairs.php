<?php
/**
 *
 */
namespace zesk;

class Control_Pairs extends Control {
    protected $options = array(
        "default" => array(),
    );

    public function validate() {
        return true;
    }

    private function _from_request() {
        $col = $this->column();
        $names = $this->request->geta($col);
        $values = $this->request->geta($col . "_value");
        $result = array();
        foreach ($names as $k => $name) {
            $value = avalue($values, $k);
            if (!empty($name) || !empty($value)) {
                $result[$name] = $value;
            }
        }
        return $result;
    }

    public function load() {
        $result = $this->_from_request();
        $this->value($result);
    }
}
