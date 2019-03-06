<?php
namespace zesk\WebApp;

use zesk\Subversion\Repository as Repo;

class Repository_Subversion extends Repository {
	public function update_versions() {
		$repo = new Repo($this->application);
		$repo->url($this->url);
		$this->versions = $repo->versions();
		return $this->store();
	}
}
