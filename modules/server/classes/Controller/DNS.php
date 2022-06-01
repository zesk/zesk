<?php declare(strict_types=1);
namespace zesk;

use zesk\Diff\Lines;

/**
 *
 * @author kent
 *
 */
class Controller_DNS extends Controller_Theme {
	/**
	 *
	 * @var string
	 */
	protected $template = 'body/default';

	/**
	 *
	 * @param unknown $old
	 * @param unknown $new
	 * @param string $reverse
	 * @return string[]
	 */
	private function _compare_results($old, $new, $reverse = false) {
		$compare_result = [];
		$old_result = $old['result'];
		$new_result = $new['result'];
		foreach ($old_result as $old_host => $old_record_type_list) {
			if (!array_key_exists($old_host, $new_result)) {
				$compare_result[] = __('{server} (query {query}) does not know about {old_host}', [
					'old_host' => $old_host,
				] + $new);

				continue;
			}
			$new_record_type_list = $new_result[$old_host];
			foreach ($old_record_type_list as $old_record_type => $old_records) {
				$new_records = avalue($new_record_type_list, $old_record_type);
				if (!is_array($new_records)) {
					$compare_result[] = __('{server} (query {query}) does not know about {old_host}/{type}', [
						'old_host' => $old_host,
						'type' => $old_record_type,
					] + $new);

					continue;
				}
				sort($old_records);
				$old_records = array_values($old_records);
				sort($new_records);
				$new_records = array_values($new_records);
				$diff = new Lines($old_records, $new_records, true);
				if ($diff->is_identical()) {
					continue;
				}
				$compare_result[] = __("< {old}, > {new}: {query} (type {type}) mismatch:\n{debug}", [
					'type' => $old_record_type,
					'debug' => HTML::tag('pre', $diff->output()),
				] + $old);
			}
		}
		return $compare_result;
	}

	/**
	 *
	 * @param unknown $old
	 * @param unknown $new
	 * @param string $old_name
	 * @param string $new_name
	 * @return string[]
	 */
	private function compare_results($old, $new, $old_name = 'old', $new_name = 'new') {
		if (!is_array($old)) {
			return [
				"$old_name lookup failed.",
			];
		}
		if (!is_array($new)) {
			return [
				"$new_name lookup failed.",
			];
		}
		return array_merge(map(self::_compare_results($old, $new), [
			'old' => $old_name,
			'new' => $new_name,
		]), map(self::_compare_results($new, $old, true), [
			'old' => $new_name,
			'new' => $old_name,
		]));
	}

	/**
	 *
	 * @param Model_DNS $model
	 * @return string
	 */
	private function run_test(Model_DNS $model) {
		$this->application->modules->load('dns;diff');

		$lookup = trim(preg_replace("/[\r\n,;]+/", "\n", $model->lookup));
		$lookup = preg_replace('/ +/', ' ', $lookup);
		$lookup = ArrayTools::listTrimClean(explode("\n", $lookup));
		$old = $model->old;
		$new = $model->new;
		$result[] = HTML::tag('h1', "Comparing $old to $new");
		$result[] = HTML::tag_open('ul');
		foreach ($lookup as $name) {
			[$type, $name] = pair($name, ' ', null, $name);
			$old_result = dns::host($name, $type, $old);
			$new_result = dns::host($name, $type, $new);
			$compare_results = self::compare_results($old_result, $new_result, $old, $new);
			if (count($compare_results) === 0) {
				$result[] = HTML::tag('li', "$name ($type) passed");
			} else {
				$result[] = HTML::tag('li', '.error', count($compare_results) === 1 ? "$name ($type) failed: <br />" . implode('', $compare_results) : "$name($type) failed" . HTML::tag('ul', HTML::tags('li', $compare_results)));
			}
			// 			$result[] = HTML::tag('pre', var_export($old_result, true));
			// 			$result[] = HTML::tag('pre', var_export($new_result, true));
		}
		$result[] = HTML::tag_close('ul');
		return implode("\n", $result);
	}

	/**
	 *
	 * @param unknown $domain
	 */
	public function action_index($domain) {
		// 		$control = new Control_Object_Edit();
		// 		$control->child($this->widget_factory(Control_Text::class)->names("old", "Old Server", true));
		// 		$control->child($this->widget_factory(Control_Text::class)->names("new", "New Server", true));
		// 		$control->child($w = $this->widget_factory(Control_Text::class)->textarea(true)->names("lookup", "Name", true));
		// 		$w->setOption('rows', 20);
		// 		$w->setOption('cols', 80);
		// 		$w->suffix(HTML::tag('label', "One per line. Domain names, optionally prefixed by query type and a space."));

		// 		$model = new Model_DNS();
		// 		$content = $control->execute($model);
		// 		if ($model->valid) {
		// 			$content .= $this->run_test($model);
		// 		}
		$content = 'TODO FIX THIS';
		return $content;
	}
}
