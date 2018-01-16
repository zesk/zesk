<?php

/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/View/OrderBy.php $
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * Created on Tue Jul 15 16:01:35 EDT 2008
 */
namespace zesk;

/**
 *
 * @deprecated use Control_OrderBy 2015-01
 * @author kent
 *
 */
class View_OrderBy extends View {
	function render() {
		$k = $this->option("list_order_column", $this->column());
		$english = $this->label();
		$html = $this->option_bool('html');
		if (!$html && $this->option_integer("show_size", -1) > 0) {
			$english = HTML::ellipsis($english, $this->option_integer("show_size"));
		}
		$order_var = $this->option("list_order_variable", "o");
		$cur_sort_names = explode(";", $this->request->get($order_var, $this->option('default')));
		$cur_sort_names = ArrayTools::clean($cur_sort_names);
		$new_order = array();
		$new_key = null;
		$sort_index = null;
		$multisort = $this->option_bool("multisort");
		$remove_order = array();
		foreach ($cur_sort_names as $i => $cur_sort_name) {
			if ($cur_sort_name === $k) {
				$sort_index = $i;
				$sort_order = "sort-asc";
				$sort_desc = "Sort ascending";
				$new_key = "-" . $k;
				$new_order[] = $new_key;
			} else if ($cur_sort_name === "-$k") {
				$sort_index = $i;
				$sort_order = "sort-desc";
				$sort_desc = "Sort descending";
				$new_key = $k;
				$new_order[] = $new_key;
			} else if ($cur_sort_name) {
				$remove_order[] = $cur_sort_name;
				$new_order[] = $cur_sort_name;
			}
		}
		if ($new_key === null) {
			if ($this->option_bool("list_order_default_ascending")) {
				$sort_desc = "Sort ascending";
				$sort_order = "sort-none";
				$new_key = $k;
			} else {
				$sort_desc = "Sort descending";
				$sort_order = "sort-none";
				$new_key = "-$k";
			}
			$new_order[] = $new_key;
		}
		if ($multisort) {
			$new_key = implode(";", $new_order);
			$remove_order = implode(";", $remove_order);
			$remove_url = URL::query_format($this->option("URI", $this->request->uri()), array(
				$order_var => $remove_order
			));
			$sort_number = ($sort_index !== null) ? HTML::tag("div", array(
				"class" => "list-order-index"
			), HTML::a($remove_url, $sort_index + 1)) : "";
		} else {
			$sort_number = "";
		}
		$url = URL::query_format($this->option("URI", $this->request->uri()), array(
			$order_var => $new_key
		));
		$a_tag = HTML::tag('a', array(
			'href' => $url
		), $english);
		$label = "<td nowrap=\"nowrap\">" . $a_tag . "</td>";
		$sort = "<td align=\"center\"><div class=\"list-order-by-icon\">$sort_number<a href=\"" . $url . "\"><img src=\"" . $this->application->url("/share/zesk/images/sort/$sort_order.gif") . "\" alt=\"$sort_desc\" width=\"16\" height=\"16\" border=\"0\" /></a></div></td>";
		
		switch ($this->option("list_order_position")) {
			case "top":
				$content = $sort . "</tr><tr>" . $label;
				break;
			case "bottom":
				$content = $label . "</tr><tr>" . $sort;
				break;
			case "left":
				$content = $sort . $label;
				break;
			default :
			case "right":
				$content = $label . $sort;
				break;
		}
		$result = str_replace('***', $content, "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" class=\"sort-widget\"><tr>***</tr></table>");
		return $result;
	}
}
