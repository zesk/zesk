<?php
namespace zesk;

class Cache_APC extends Cache {
	
	/**
	 * Bump when internal APC structure has an incompatibility with existing cached objects.
	 *
	 * @var integer
	 */
	const version = 1;
	private $success = false;
	private $expire_after = null;
	protected function exists() {
		if ($this->_load) {
			$this->load();
		}
		return $this->success;
	}
	protected function fetch() {
		$value = apc_fetch($this->_name, $this->success);
		if (!$this->success || !is_array($value)) {
			$this->erase();
		} else {
			$version = $value['version'];
			$this->_data = $value['data'];
			$this->_internal = $value['internal'];
		}
		$this->_dirty = false;
		$this->_load = false;
	}
	protected function store() {
		apc_store($this->name, array(
			"data" => $this->_data,
			"internal" => $this->_internal
		));
		$this->_dirty = false;
	}
	public function expire_after($n_seconds) {
		$this->expire_after = $n_seconds;
		return $this;
	}
	protected function delete() {
		apc_delete($this->_name);
	}
}
