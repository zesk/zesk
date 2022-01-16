<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
namespace zesk;

/**
 * @todo test this
 * @author kent
 *
 */
class View_CSV extends View {
	public function render() {
		$table = $this->value();
		if (!is_array($table)) {
			return $this->empty_string();
		}

		$header_rows_top = $this->optionInt("header_rows_top", $this->optionInt('header_rows', 1));
		$header_rows_left = $this->optionInt("header_rows_left", 0);

		foreach ($table as $row_index => $row) {
			[$tag, $attrs] = ($row_index < $header_rows_top) ? [
				"th",
				$this->oa('th_attrs'),
			] : [
				"td",
				$this->oa('td_attrs'),
			];
			if ($header_rows_left > 0) {
				$html[] = self::smart_tags('th', $attrs, array_slice($row, 0, $header_rows_left)) . self::smart_tags($tag, $attrs, array_slice($row, $header_rows_left));
			} else {
				$html[] = self::smart_tags($tag, $attrs, $row);
			}
		}

		if ($this->optionBool('rows_even_odd')) {
			$rows = "";
			foreach ($html as $i => $row) {
				$rows .= HTML::tag('tr', [
					'class' => 'row-' . ($i & 1),
				], $row);
			}
		} else {
			$rows = HTML::tags('tr', $html);
		}
		return HTML::tag('table', $this->option_array('table_attributes', [
			'cellspacing' => 1,
			'cellpadding' => 4,
			'border' => 0,
		]), $rows);
	}

	public static function smart_tags($tag, array $attrs, array $cells) {
		$html = [];
		foreach ($cells as $cell) {
			$call_attrs = $attrs;
			if (is_numeric($cell) || is_date($cell)) {
				$call_attrs['align'] = "right";
			}
			$html[] = HTML::tag($tag, $call_attrs, $cell);
		}
		return implode("", $html);
	}

	public static function html(array $table, $options = null) {
		$view = new View_CSV($options);
		$view->names("table");
		return $view->output([
			"table" => $table,
		]);
	}
}
