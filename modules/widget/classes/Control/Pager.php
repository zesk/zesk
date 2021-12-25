<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * Created on Tue Jul 15 16:27:00 EDT 2008
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Control_Pager extends Control {
	/**
	 *
	 * @param Kernel $zesk
	 */
	public static function hooks(Application $zesk): void {
		$zesk->configuration->deprecated([
			__CLASS__,
			"pager_limit",
		], [
			__CLASS__,
			"limit",
		]);
		$zesk->configuration->deprecated("pager_limit", [
			__CLASS__,
			"limit",
		]);
	}

	/**
	 *
	 * @var boolean
	 */
	protected bool $traverse = true;

	/*
	 * @var Control_Select
	 */
	protected Control_Select $limit_widget;

	/**
	 *
	 * @var array
	 */
	protected array $options = [
		'column' => 'pager',
	];

	/**
	 *
	 * {@inheritDoc}
	 * @see Widget::model()
	 */
	public function model() {
		return new Model_List($this->application);
	}

	public function preserve_hidden($name, $value = null) {
		$variables = avalue($this->theme_variables, 'preserve_hidden');
		if (!is_array($variables)) {
			$variables = [];
		}
		if (is_array($name)) {
			$variables = $name + $variables;
			return $this;
		}
		$variables[$name] = $value;
		$this->theme_variables['preserve_hidden'] = $variables;
		return $this;
	}

	public function limit_default() {
		return $this->option_integer("limit");
	}

	private function _limit_widget() {
		$pager_limit_list = $this->pager_limit_list();

		$pager_limit_list = ArrayTools::flip_copy($pager_limit_list);

		$ajax_id = $this->option('ajax_id');
		$onchange = $ajax_id ? "pager_limit_change.call(this,'$ajax_id')" : "this.form.submit()";

		$options = [];
		$options['options'] = $pager_limit_list;
		$options['onchange'] = $onchange;
		$options['default'] = $this->request->geti('limit', $this->limit_default());
		$options['skip_query_condition'] = true;
		$options['query_column'] = [];

		return $this->widget_factory(Control_Select::class)
			->names('limit')
			->required(true)
			->set_option($options);
	}

	protected function initialize(): void {
		$this->set_option('max_limit', $this->_maximum_limit());
		$this->limit_widget = $this->_limit_widget();
		$this->child($this->limit_widget);
		parent::initialize();
	}

	protected function defaults(): void {
		$this->children_defaults();
		$this->object->set('offset', $this->request->geti("offset", 0));
	}

	private function _refresh(): void {
		$object = $this->object;
		$total = to_integer($object->total, -1);
		$off = $object->offset;
		$lim = $object->limit;
		$last_offset = -1;
		if ($total >= 0) {
			if ($lim < 0) {
				$off = 0;
				$lim = -1;
			} else {
				if ($lim == 0) {
					$last_offset = 0;
					$off = 0;
				} else {
					$last_offset = (intval(floatval($total - 1) / $lim)) * $lim;
					$max_lim = $this->option('max_limit', 100);
					if ($lim > $max_lim) {
						$lim = $max_lim;
					}
					if ($off > $total) {
						$off = intval($total / $lim) * $lim;
					} elseif ($off < 0) {
						$off = 1;
					}
				}
			}
		}
		$object->offset = $off;
		$object->limit = $lim;
		$object->total = $total;
		$object->last_offset = $last_offset;
	}

	protected function hook_total($total): void {
		$this->object->total = $total;
		$this->_refresh();
	}

	/**
	 * @param Database_Query_Select $query
	 * @throws Exception_Semantics
	 */
	protected function hook_query_list(Database_Query_Select $query): void {
		static $recurse = 0;
		if ($recurse !== 0) {
			throw new Exception_Semantics("Can not call hook_query_list recursively");
		}
		$recurse = 1;
		$query->limit($this->object->offset, $this->object->limit);
		// KMD 2016-06-15 Why was this commented out?
		foreach ($this->children as $child) {
			/* @var $child Widget */
			if ($child === $this->limit_widget) {
				continue;
			}
			$child->children_hook_array('query_list', [
				$query,
			]);
		}
		$recurse = 0;
	}

	public function hook_render(): void {
		$this->_refresh();
		$object = $this->object;
		$pager_limit_list = $this->limit_widget->control_options();

		$ss = [];
		$limit = intval($object->limit);
		$total = $object->total;
		if ($limit < 0) {
			$limit = $total;
		}
		$show_all = $this->option_bool('pager_show_all');
		$show_all_string = $this->option("pager_all_string", $this->application->locale->__("All"));
		if ($show_all) {
			$ss[-1] = $show_all_string;
		}
		foreach ($pager_limit_list as $i => $val) {
			$i = intval($i);
			if ($i < 0) {
				continue;
			}
			if ($i < $total) {
				if ($limit < $i) {
					$ss[$limit] = $limit;
				}
				$ss[$i] = $i;
			} elseif (!$show_all) {
				if ($object->limit > $i) {
					$object->limit = $i;
				}
				$ss[$i] = $show_all_string;

				break;
			}
		}
		$this->limit_widget->control_options($ss);
	}

	public function pager_limit_list($set = null) {
		if ($set !== null) {
			return $this->set_option('pager_limit_list', to_list($set));
		}
		return $this->option_list('pager_limit_list', '5;10;25;50;100');
	}

	private function _maximum_limit() {
		$n = $this->option_integer("pager_maximum_limit", false);
		if ($n !== false) {
			return $n;
		}
		$limit_list = $this->pager_limit_list();
		$max = 0;
		foreach ($limit_list as $k) {
			$max = max($max, intval($k));
		}
		return $max;
	}
}
