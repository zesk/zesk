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
class Control_OrderBy extends Control {
    /**
     *
     * @var string
     */
    const default_order_variable = 'o';

    /**
     *
     * @var unknown
     */
    private $active = null;

    /**
     *
     * @var unknown
     */
    private $sort_number;

    /**
     *
     * @var unknown
     */
    private $sort_order;

    /**
     *
     * @var unknown
     */
    private $url = null;

    /**
     * Getter/setter for
     * @param unknown $set
     * @return void|mixed|string
     */
    public function list_order_by($set = null) {
        return $set === null ? $this->option('list_order_by') : $this->set_option('list_order_by', $set);
    }

    public function list_order_by_reverse($set = null) {
        if ($set === null) {
            $result = $this->option('list_order_by_reverse', null);
            if ($result !== null) {
                return $result;
            }
            $list_order_by = to_list($this->list_order_by());
            $reverse_order_by = array();
            foreach ($list_order_by as $token) {
                $lowtoken = strtolower($token);
                if (ends($lowtoken, " desc")) {
                    $reverse_order_by[] = substr($token, 0, -5);
                } elseif (ends($lowtoken, " asc")) {
                    $reverse_order_by[] = substr($token, 0, -4) . " DESC";
                } else {
                    $reverse_order_by[] = "$token DESC";
                }
            }
            return $reverse_order_by;
        }
        return $this->set_option('list_order_by_reverse', $set);
    }

    public function list_order_column($set = null) {
        return $set === null ? $this->option('list_order_column', $this->column()) : $this->set_option('list_order_column', $set);
    }

    public function list_order_variable($set = null) {
        if ($set === null) {
            return $this->option('list_order_variable', $this->parent ? $this->parent->option('list_order_variable', self::default_order_variable) : self::default_order_variable);
        }
        return $this->set_option('list_order_variable', $set);
    }

    public function hook_header(Control_Header $header) {
        $col = $this->list_order_column();
        $header->add_ordering($col, $this->list_order_by());
        $header->add_ordering("-$col", $this->list_order_by_reverse());
    }

    public function initialize() {
        parent::initialize();
        $list_order_by = $this->list_order_by();
        if ($list_order_by === true) {
            $this->list_order_by($this->column());
        }
        $cur_sort_names = ArrayTools::clean($this->request->geta($this->list_order_variable(), array(), ";"));
        $k = $this->list_order_column();
        $order_var = $this->list_order_variable();
        $new_order = array();
        $new_key = null;
        $sort_index = null;
        $multisort = $this->option_bool("multisort");
        $remove_order = array();
        $selected = false;
        $ascending = true;
        foreach ($cur_sort_names as $i => $cur_sort_name) {
            if ($cur_sort_name === $k) {
                $sort_index = $i;
                $sort_order = "sort-asc";
                $selected = true;
                $alt = "Sort ascending";
                $ascending = true;
                $new_key = "-" . $k;
                $new_order[] = $new_key;
            } elseif ($cur_sort_name === "-$k") {
                $sort_index = $i;
                $selected = true;
                $sort_order = "sort-desc";
                $alt = "Sort descending";
                $ascending = false;
                $new_key = $k;
                $new_order[] = $new_key;
            } elseif ($cur_sort_name) {
                $remove_order[] = $cur_sort_name;
                $new_order[] = $cur_sort_name;
            }
        }
        if ($new_key === null) {
            if ($this->option_bool("list_order_default_ascending", true)) {
                $alt = "Sort ascending";
                $sort_order = "sort-none";
                $ascending = true;
                $new_key = $k;
            } else {
                $alt = "Sort descending";
                $ascending = false;
                $sort_order = "sort-none";
                $new_key = "-$k";
            }
            $new_order[] = $new_key;
        }
        if ($multisort) {
            $new_key = implode(";", $new_order);
            $remove_order = implode(";", $remove_order);
            $remove_url = URL::query_format($this->option("URI", $this->request->uri()), array(
                $order_var => $remove_order,
            ));
            $sort_number = ($sort_index !== null) ? HTML::tag("div", array(
                "class" => "list-order-index",
            ), HTML::a($remove_url, $sort_index + 1)) : "";
        } else {
            $sort_number = null;
        }
        $new_query = array(
            $order_var => $new_key,
        );
        $this->theme_variables['orderby_url'] = URL::query_format($this->option("URI", $this->request->uri()), $new_query);
        
        $this->theme_variables['list_order_column'] = $k;
        $this->theme_variables['list_order_variable'] = $order_var;
        $this->theme_variables['ascending'] = $ascending;
        $this->theme_variables['selected'] = $selected;
        $this->theme_variables['sort_order'] = $sort_order;
        $this->theme_variables['alt'] = $alt;
        $this->theme_variables['sort_number'] = $sort_number;
        $this->theme_variables['sort_order'] = $sort_order;
    }
}
