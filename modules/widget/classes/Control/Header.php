<?php
/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Control_Header extends Control {
    protected $order_by_map = array();

    public function list_order_variable($set = null) {
        return $set === null ? $this->option('list_order_variable', 'o') : $this->set_option('list_order_variable', $set);
    }

    /**
     * Called within hook_header, usually
     * @param string $k
     * @param string $sql Order by column clause
     */
    public function add_ordering($k, $sql) {
        $this->order_by_map[$k] = $sql;
    }

    public function hook_initialized() {
        $this->children_hook('header', $this);
    }

    public function hook_query_list(Database_Query_Select $query) {
        $variable = $this->list_order_variable();
        $value = $this->request->get($variable);
        if (array_key_exists($value, $this->order_by_map)) {
            $query->order_by($this->order_by_map[$value]);
        }
    }
}
