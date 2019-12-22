<?php

/**
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * Created on Tue Jul 15 16:27:40 EDT 2008
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Control_Order extends Control {
	public function __construct($options = false) {
		parent::__construct($options);
		$this->set_option("nowrap", "nowrap");
		$this->set_option("ObjectListCheck", true);
	}

	protected function hook_query_list(Database_Query_Select $query) {
		$query->what($this->column(), "X." . $this->column());
		$query->order_by($this->column());
	}

	public function submitted() {
		return $this->request->get("move") !== null;
	}

	public function validate() {
		$verb = $this->request->get("move");
		$ID = $this->request->get("ID");
		if (!Lists::contains("up;down;top;bottom", $verb) || $ID == "") {
			return true;
		}
		if ($this->option_bool('debug')) {
			$this->response()->debug_redirect(true);
		}
		if (Lists::contains("top;bottom", $verb)) {
			$this->moveTopBottom($ID, $verb);
		} else {
			$this->moveUpDown($ID, $verb);
		}
		$uri = $this->request->uri();
		$newURL = URL::query_remove($uri, "move;message", false);

		throw new Exception_RedirectTemporary($newURL);
	}

	public function render() {
		$ID = $this->object->id();

		$u = URL::query_format(URL::query_remove($this->request->uri(), "move", false), array(
			"ID" => $ID,
		));

		$result = array();
		$result[] = HTML::tag("a", array(
			"href" => "$u&move=top",
		), HTML::img($this->application, "/share/zesk/images/order/move-top.gif", "Move to top"));
		$result[] = HTML::tag("a", array(
			"href" => "$u&move=up",
		), HTML::img($this->application, "/share/zesk/images/order/move-up.gif", "Move up"));
		$result[] = HTML::tag("a", array(
			"href" => "$u&move=down",
		), HTML::img($this->application, "/share/zesk/images/order/move-down.gif", "Move down"));
		$result[] = HTML::tag("a", array(
			"href" => "$u&move=bottom",
		), HTML::img($this->application, "/share/zesk/images/order/move-bottom.gif", "Move to bottom"));

		$result = implode("&nbsp;", $result);

		if ($this->option_bool('debug')) {
			$result .= ' (' . $this->value() . ')';
		}
		return $result;
	}

	private function _where() {
		return map($this->object->apply_map($this->option_array("where", array())), $this->request->variables());
	}

	private function moveTopBottom($ID, $verb) {
		$table = $this->option("table", "no_table");
		$col = $this->column();
		if ($verb == "top") {
			$func = "MIN";
			$delta = -1;
		} else {
			$func = "MAX";
			$delta = 1;
		}
		$db = $this->object->database();
		$dbsql = $db->sql();
		$sql = "SELECT $func(`$col`) AS N FROM `$table`" . $dbsql->where($this->_where());
		$nextOrder = $db->query_one($sql, "N", 1);
		$db->query("UPDATE `$table` SET `$col`=" . ($nextOrder + $delta) . " WHERE ID=$ID");
	}

	private function moveUpDown($ID, $verb) {
		$table = $this->option("table", "no_table");
		$col = $this->column();
		if ($verb == "up") {
			$sign = "+";
			$cmp = "<=";
			$icmp = ">=";
			$order_by = "$col DESC";
		} else {
			$sign = "-";
			$cmp = ">=";
			$icmp = "<=";
			$order_by = "$col ASC";
		}

		$where = $this->_where();
		$where["ID"] = $ID;

		$db = $this->object->database();

		$sqlgen = $db->sql();
		$sql = "SELECT `$col` AS N FROM `$table` " . $sqlgen->where($where);
		$thisOrder = $db->query_one($sql, "N", false);
		$nextObject = null;

		if (is_numeric($thisOrder)) {
			$where = $this->_where();
			$where["ID|!="] = $ID;
			$where["$col|$cmp"] = $thisOrder;
			$sql = "SELECT `ID`,`$col` AS N FROM `$table` " . $sqlgen->where($where) . $sqlgen->order_by($order_by);
			$nextObject = $db->query_one($sql);
		}
		if (!$nextObject) {
			$this->moveTopBottom($ID, $verb == "up" ? "top" : "bottom");
			return;
		}
		if (intval($nextObject["N"]) != intval($thisOrder)) {
			$sql = "UPDATE `$table` SET `$col`=$thisOrder WHERE ID=" . $nextObject["ID"];
			$db->query($sql);
			$sql = "UPDATE `$table` SET `$col`=" . $nextObject["N"] . " WHERE ID=$ID";
			$db->query($sql);
			return;
		}
		$where = $this->_where();
		$where['ID|!='] = $ID;
		$where["$col|$icmp"] = $thisOrder;
		$sql = "UPDATE `$table` SET `$col`=`$col`${sign}5 " . $sqlgen->where($where);

		$db->query($sql);
	}
}
