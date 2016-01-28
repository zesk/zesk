<?php

class Module_Moment extends Module_JSLib {
	protected $javascript_paths = array(
		"/share/moment/moment.js",
		"/share/moment/moment-with-locales.js"
	);

	public function initialize() {
		if ($this->option_bool('timezone_support')) {
			$this->javascript_paths[] = '/share/moment/moment-timezone.js';
			$this->javascript_paths[] = '/share/moment/moment-timezone-with-data-2010-2020.js';
			$this->javascript_paths[] = '/share/moment/moment-timezone-with-data.js';
		}
		parent::initialize();
	}
}
