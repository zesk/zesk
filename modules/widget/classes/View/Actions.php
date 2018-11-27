<?php

/**
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * Created on Tue Jun 17 00:58:51 EDT 2008
 */
namespace zesk;

/**
 * @see theme/zesk/view/actions.tpl
 * @see theme/zesk/view/action.tpl
 * @see theme/zesk/view/action/edit.tpl
 * @see theme/zesk/view/action/delete.tpl
 * @author kent
 *
 */
class View_Actions extends View {
    /**
     *
     * {@inheritDoc}
     * @see \zesk\Widget::initialize()
     */
    protected function initialize() {
        parent::initialize();
        $this->set_option(array(
            "list_order_by" => false,
            "class" => "view-actions",
            "label" => $this->locale()->__("Actions"),
        ), false, false);
        foreach (array(
            "edit",
            "delete",
        ) as $action_code) {
            // show_edit
            // show_delete
            if ($this->option("show_" . $action_code, true)) {
                $url = $this->_action_href($action_code);
                if ($url) {
                    $this->action_add($url, array(
                        "theme" => "zesk/view/actions/$action_code",
                        "action_code" => $action_code,
                        "a_attributes" => array(
                            "class" => "action-$action_code",
                        ),
                        "add_link" => true,
                    ));
                }
            }
        }
    }

    /**
     * Fetch URL for action by name
     *
     * @param string $action
     * @param boolean $add_ref Add referring page to link
     * @return \zesk\Model|mixed|string|\zesk\Hookable|number|array|string|NULL
     */
    private function _action_href($action, $add_ref = true) {
        $object = $this->object;
        if ($this->has_option($action . "_href")) {
            return $object->apply_map($this->option($action . '_href'));
        }
        if (!$object) {
            return null;
        }
        return $this->application->router()->get_route($action, $object, $this->option_array("route_options"));
    }

    /**
     *
     * @param string $set
     * @return self|string
     */
    public function format($set = null) {
        return ($set !== null) ? $this->set_option('format', $set) : $this->option('format');
    }

    /**
     *
     * @param unknown $url
     * @param array $options
     * @return \zesk\View_Actions
     */
    public function action_add($url, array $options = array()) {
        $options['url'] = $url;
        $actions = $this->actions();
        $actions[] = $options;
        $this->actions($actions);
        return $this;
    }

    /**
     * Getter/setter for action list (array of arrays with keys: url, add_link, [theme | content | text | title]
     * @param array $set
     * @return array|void
     */
    public function actions(array $set = null) {
        return $set === null ? $this->option_list("actions") : $this->set_option("actions", $set);
    }

    /**
     *
     * {@inheritDoc}
     * @see \zesk\View::theme_variables()
     */
    public function theme_variables() {
        return array(
            "actions" => $this->actions(),
            "add_href" => $this->option_bool("add_href", true),
        );
    }
}
