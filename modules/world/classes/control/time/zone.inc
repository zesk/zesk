<?php
class Control_Time_Zone extends Control_Select {
	protected $theme = "control/select";
	protected $where = array();
	public function where(array $where = null) {
		if ($where === null) {
			return $this->where;
		}
		$this->where = $where;
		return $this;
	}

	public function prefixes_only($set = null) {
		return $set === null ? $this->option_bool('prefixes_only', false) : $this->set_option('prefixes_only', to_bool($set));
	}

	function initialize() {
		$query = Object::class_query("Time_Zone")->where($this->where);
		if ($this->prefixes_only()) {
			$query->what("*Name", "DISTINCT LEFT(Name,LOCATE('/',Name)-1)");
			$tzs = arr::clean(arr::ksuffix($query->to_array("Name", "Name"), "/"), '');
			$this->control_options($tzs);
		} else {
			$query->what("Name", "Name");
			$exclude = $this->option_array('exclude');
			if (count($exclude) > 0) {
				$query->where("Name|NOT LIKE|AND", $exclude);
			}
			$tzs = $query->to_array("Name", "Name");
			$parents = array();
			foreach ($tzs as $tz) {
				list($region, $name) = pair($tz, "/", "Miscellaneous", $tz);
				$region = strtr($region, "_", " ");
				$parents[$region][$tz] = strtr($name, "_", " ");
			}
			$this->set_option('optgroup', true);
			$this->control_options($parents);
		}
		parent::initialize();
	}
}
