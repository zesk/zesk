<?php
class DNS_Model extends Model {
	public $valid = false;
	public $old = null;
	public $new = null;
	public $lookup = null;
	function store() {
		$this->valid = true;
	}
}
class Controller_DNS extends Controller_Template {
	protected $template = "body/default.tpl";
	private function _compare_results($old, $new, $reverse = false) {
		$compare_result = array();
		$old_result = $old['result'];
		$new_result = $new['result'];
		foreach ($old_result as $old_host => $old_record_type_list) {
			if (!array_key_exists($old_host, $new_result)) {
				$compare_result[] = __("{server} (query {query}) does not know about {old_host}", array(
					'old_host' => $old_host
				) + $new);
				continue;
			}
			$new_record_type_list = $new_result[$old_host];
			foreach ($old_record_type_list as $old_record_type => $old_records) {
				$new_records = avalue($new_record_type_list, $old_record_type);
				if (!is_array($new_records)) {
					$compare_result[] = __("{server} (query {query}) does not know about {old_host}/{type}", array(
						'old_host' => $old_host,
						'type' => $old_record_type
					) + $new);
					continue;
				}
				sort($old_records);
				$old_records = array_values($old_records);
				sort($new_records);
				$new_records = array_values($new_records);
				$diff = new Diff_Lines($old_records, $new_records, true);
				if ($diff->is_identical()) {
					continue;
				}
				$compare_result[] = __("< {old}, > {new}: {query} (type {type}) mismatch:\n{debug}", array(
					'type' => $old_record_type,
					'debug' => html::tag('pre', $diff->output())
				) + $old);
			}
		}
		return $compare_result;
	}
	private function compare_results($old, $new, $old_name = "old", $new_name = "new") {
		if (!is_array($old)) {
			return array(
				"$old_name lookup failed."
			);
		}
		if (!is_array($new)) {
			return array(
				"$new_name lookup failed."
			);
		}
		return array_merge(map(self::_compare_results($old, $new), array(
			"old" => $old_name,
			"new" => $new_name
		)), map(self::_compare_results($new, $old, true), array(
			'old' => $new_name,
			"new" => $old_name
		)));
	}
	private function run_test(DNS_Model $model) {
		Module::load("dns;diff");
		$lookup = trim(preg_replace("/[\r\n,;]+/", "\n", $model->lookup));
		$lookup = preg_replace('/ +/', ' ', $lookup);
		$lookup = arr::trim_clean(explode("\n", $lookup));
		$old = $model->old;
		$new = $model->new;
		$result[] = html::tag('h1', "Comparing $old to $new");
		$result[] = html::tag_open("ul");
		foreach ($lookup as $name) {
			list($type, $name) = pair($name, " ", null, $name);
			$old_result = dns::host($name, $type, $old);
			$new_result = dns::host($name, $type, $new);
			$compare_results = self::compare_results($old_result, $new_result, $old, $new);
			if (count($compare_results) === 0) {
				$result[] = html::tag("li", "$name ($type) passed");
			} else {
				$result[] = html::tag("li", ".error", count($compare_results) === 1 ? "$name ($type) failed: <br />" . implode("", $compare_results) : "$name($type) failed" . html::tag("ul", html::tags('li', $compare_results)));
			}
			// 			$result[] = html::tag('pre', var_export($old_result, true));
			// 			$result[] = html::tag('pre', var_export($new_result, true));
		}
		$result[] = html::tag_close("ul");
		return implode("\n", $result);
	}
	function action_index($domain) {
		$control = new Control_Object_Edit();
		$control->child(widgets::control_text("old", "Old Server", true));
		$control->child(widgets::control_text("new", "New Server", true));
		$control->child($w = widgets::control_textarea("lookup", "Name", true));
		$w->set_option('rows', 20);
		$w->set_option('cols', 80);
		$w->suffix(html::tag('label', "One per line. Domain names, optionally prefixed by query type and a space."));

		$model = new DNS_Model();
		$content = $control->execute($model);
		if ($model->valid) {
			$content .= $this->run_test($model);
		}
		$this->template->content = $content;
	}
}
