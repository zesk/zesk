<?php declare(strict_types=1);
namespace zesk;

class Control_Binary extends Control_Filter_Query {
	protected function initialize(): void {
		if (count($this->query_options) === 0) {
			$query_column = $this->queryColumn();
			$locale = $this->application->locale;
			$this->query_options([
				0 => [
					'title' => $locale->__('No'),
					'where' => [
						$query_column => 0,
					],
					'condition' => $locale->__('{label} is no'),
				],
				1 => [
					'title' => $locale->__('Yes'),
					'where' => [
						$query_column => 1,
					],
					'condition' => $locale->__('{label} is yes'),
				],
			]);
		}
		parent::initialize();
	}
}
