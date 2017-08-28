<?php
/**
* $URL: https://code.marketacumen.com/zesk/trunk/classes/Control/IP/List.php $
* @package zesk
* @subpackage default
* @author Kent Davidson <kent@marketacumen.com>
* @copyright Copyright &copy; 2009, Market Acumen, Inc.
*/
namespace zesk;

class Control_IP_List extends Control {

	private $ErrorIPs;

	function load() {
		$name = $this->name();
		$value = trim($this->request->get($name, "") . ' ' . $this->request->get($name . '_errors', ''));
		$value = preg_replace("#[^.*0-9/ ]#", "", $value);
		$this->value($value);
		return parent::load();
	}

	function validate() {
		$allow_ip_masks = $this->option("allow_ip_masks", false);
		$col = $this->column();
		$name = $this->name();
		$value = $this->value();
		$iplist = arr::trim(explode(' ', preg_replace('/[\s, ]+/', ' ', $value)));
		//		sort($iplist, SORT_NUMERIC);
		$check_func = $allow_ip_masks ? "IPv4::is_mask" : "IPv4::valid";
		foreach ($iplist as $k => $ip) {
			if (empty($ip)) {
				unset($iplist[$k]);
				continue;
			}
			if (!call_user_func($check_func, $ip)) {
				$this->ErrorIPs[] = $ip;
				unset($iplist[$k]);
			}
		}
		$this->value(implode("\n", $iplist));
		if (count($this->ErrorIPs) > 0) {
			$this->setError($col, "Some IP addresses were incorrectly formatted, please check your work below.");
			return false;
		}
		return true;
	}

	function render() {
		$col = $this->column();
		$name = $this->name();
		$errors = "";
		$attrs = $this->option(array(
			"rows" => 10,
			"cols" => 20,
			"id" => $col . "_ip_list",
			"name" => $name
		));
		$ip = $this->request->ip();
		$this->response->cdn_javascript('/share/zesk/widgets/iplist/iplist.js');
		$this->response->cdn_css('/share/zesk/widgets/iplist/iplist.css');

		$add_ip = "<a class=\"ip-list-add\" id=\"${name}\" onclick=\"iplist_add('${col}_ip_list', '${ip}')\">" . __('Add current IP') . ": $ip</a>";
		if (count($this->ErrorIPs)) {
			$err_attrs = $attrs;
			$err_attrs['name'] = $name . '_errors';
			$err_attrs['id'] = $col . '_ip_list_errors';
			$errors = HTML::tag("div", array(
				"class" => "ip-list ip-list-errors"
			), HTML::tag("div", array(
				"class" => "ip-list-textarea"
			), HTML::tag("textarea", $err_attrs, implode("\n", $this->ErrorIPs))) . HTML::etag("label", false, $this->option("error_ip_list_label", "Errors")));
		}
		$result = HTML::tag("div", array(
			"class" => "ip-list"
		), HTML::tag("div", array(
			"class" => "ip-list-textarea"
		), HTML::tag("textarea", $attrs, $this->value()) . $add_ip) . HTML::etag("label", false, $this->option("ip_list_label", ""))) . $errors;
		$result = HTML::tag("div", array(
			"class" => "ip-list-widget"
		), $result);
		return $result;
	}
}

