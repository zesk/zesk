<?php declare(strict_types=1);

/**
 * @see Class_County
 * @author kent
 *
 */
namespace zesk;

/**
 * @see Class_County
 * @author kent
 *
 */
class County extends ORM {
	public function reassign($new): void {
		$old_id = intval($this->id);
		$new_id = empty($new) ? null : ORM::mixed_to_id($new);
		$this->application->orm_registry(Contact_Address::class)
			->query_update()
			->ignore_constraints(true)
			->value('county', $new_id)
			->where('county', $old_id)
			->execute();
	}

	public function usage_statistics() {
		return [
			'Contact_Address' => $this->application->orm_registry(Contact_Address::class)
				->query_select()
				->where('county', $this->id)
				->addWhat('*total', 'COUNT(X.id)')
				->one_integer('total'),
		];
	}

	public static function permissions(Application $application) {
		return parent::default_permissions($application, __CLASS__);
	}
}
