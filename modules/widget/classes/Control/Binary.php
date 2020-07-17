<?php
namespace zesk;

class Control_Binary extends Control_Filter_Query {
	protected function initialize() {
		if (count($this->query_options) === 0) {
			$query_column = $this->query_column();
			$locale = $this->application->locale;
			$this->query_options(array(
				0 => array(
					'title' => $locale->__('No'),
					'where' => array(
						$query_column => 0,
					),
					'condition' => $locale->__('{label} is no'),
				),
				1 => array(
					'title' => $locale->__('Yes'),
					'where' => array(
						$query_column => 1,
					),
					'condition' => $locale->__('{label} is yes'),
				),
			));
		}
		parent::initialize();
	}
}
