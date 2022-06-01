<?php declare(strict_types=1);
namespace zesk;

class Control_Roles extends Control_Checklist_Object {
	/**
	 *
	 * @var string
	 */
	protected $class = 'zesk\\Role';

	public function initialize(): void {
		parent::initialize();

		if (!$this->userCan('zesk\\Role::view_all')) {
			$this->options['where'] = [
				'OR' => [
					'X.visibility' => 1,
				],
			];
		}
	}
}
