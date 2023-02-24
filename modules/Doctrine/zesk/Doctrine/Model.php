<?php declare(strict_types=1);
/**
 *
 */
namespace zesk\Doctrine;

use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\EntityManager;

class Model extends \zesk\Model {
	protected EntityManager $em;

	public function initialize(): void {
		$this->em = $this->application->entityManager();
	}

	/**
	 * @return void
	 * @throws ORMException
	 */
	public function delete(): void {
		$this->em->remove($this);
	}

	public static function basePermissions(): array {
		return [
			'view' => [
				'title' => 'View {object}',
				'class' => '{class}',
				'before_hook' => [
					'allowed_if_all' => ['{class}::view all', ],
				],
			],
			'view all' => [
				'title' => 'View all {objects}',
			],
			'edit' => [
				'title' => 'Edit {object}',
				'class' => '{class}',
				'before_hook' => [
					'allowed_if_all' => ['{class}::edit all', ],
				],
			],
			'edit all' => [
				'title' => 'Edit all {objects}',
			],
			'new' => [
				'title' => 'Create {objects}',
			],
			'delete all' => [
				'title' => 'Delete any {objects}',
			],
			'delete' => [
				'title' => 'Delete {object}',
				'before_hook' => ['allowed_if_all' => ['{class}::delete all', ], ],
				'class' => '{class}',
			],
			'list' => [
				'title' => 'List {objects}',
			],
		];
	}
}
