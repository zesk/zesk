<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 * Created on Tue Jul 15 16:15:44 EDT 2008
 */
namespace zesk;

class View_Checklist extends View {
	// 	function defaults(Model $object) {
	// 		if ($this->hasOption("table")) {
	// 			$table = $this->option("table");
	// 			$linkCol = $this->option("column");
	// 			$idCol = $this->optionget("idcolumn");
	// 			$id = avalue($object, "ID");
	// 			if (!empty($id)) {
	// 				$v = d  b::queryArray("SELECT `$idCol`,`$linkCol` FROM `$table` WHERE `$idCol`=" . avalue($object, "ID"), false, $linkCol);
	// 			} else {
	// 				$v = array();
	// 			}
	// 			return $v;
	// 		}
	// 	}

	// 	function load(Model $object) {
	// 		if ($this->hasOption("Separator")) {
	// 			$sep = $this->option("Separator");
	// 			$v = implode($sep, $this->request->getArray($this->name(), $this->option("default", array()), $sep));
	// 			$this->value($object, $v);
	// 		} else {
	// 			parent::load($object);
	// 		}
	// 	}
	public function render(): string {
		$oopt = HTML::parse_attributes($this->option('options', ''));
		$v = $this->value();
		if (!is_array($v)) {
			$sep = $this->option('separator', ';');
			if (is_string($v)) {
				if ($sep === '') {
					$v = str_split($v, 1);
				} else {
					$v = explode($sep, $v);
				}
			} else {
				$v = [];
			}
		}

		$r = [];
		foreach ($oopt as $k => $text) {
			if (in_array($k, $v)) {
				$r[] = $text;
			}
		}
		if (count($r) === 0) {
			$r = $this->empty_string();
		} else {
			$r = implode($this->option('between', ', '), $r);
		}
		return $r;
	}
}
