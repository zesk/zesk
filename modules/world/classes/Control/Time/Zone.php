<?php declare(strict_types=1);
namespace zesk;

class Control_Time_Zone extends Control_Select {
	/**
	 *
	 * @var array
	 */
	protected $where = [];

	/**
	 *
	 * @param array $where
	 * @return \zesk\Control_Time_Zone
	 */
	public function where(array $where = null) {
		if ($where === null) {
			return $this->where;
		}
		$this->where = $where;
		return $this;
	}

	/**
	 *
	 * @param unknown $set
	 * @return void|mixed|boolean
	 */
	public function prefixes_only($set = null) {
		return $set === null ? $this->optionBool('prefixes_only', false) : $this->setOption('prefixes_only', toBool($set));
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Control_Select::initialize()
	 */
	public function initialize(): void {
		$query = $this->application->ormRegistry('zesk\\Time_Zone')->query_select()->where($this->where);
		if ($this->prefixes_only()) {
			$query->what('*Name', 'DISTINCT LEFT(Name,LOCATE(\'/\',Name)-1)');
			$tzs = ArrayTools::clean(ArrayTools::suffixKeys($query->to_array('Name', 'Name'), '/'), '');
			$this->control_options($tzs);
		} else {
			$query->what('Name', 'Name');
			$exclude = $this->optionArray('exclude');
			if (count($exclude) > 0) {
				$query->addWhere('Name|NOT LIKE|AND', $exclude);
			}
			$tzs = $query->to_array('Name', 'Name');
			$parents = [];
			foreach ($tzs as $tz) {
				[$region, $name] = pair($tz, '/', 'Miscellaneous', $tz);
				$region = strtr($region, '_', ' ');
				$parents[$region][$tz] = strtr($name, '_', ' ');
			}
			$this->setOption('optgroup', true);
			$this->control_options($parents);
		}
		parent::initialize();
	}
}
