<?php declare(strict_types=1);
namespace zesk;

/**
 * List all permissions available in the system
 *
 * @author kent
 * @category Permission
 * @see Module_Permission
 */
class Command_Permission_List extends Command_Base {
	public array $option_types = [
		'format' => 'string',
	];

	public function run() {
		$perms = $this->application->permissions_module()->permissions();
		foreach ($perms['class'] as $class => $actions) {
			foreach ($actions as $codename => $permission) {
				assert($permission instanceof Permission);
				apath_set($perms, [
					'class',
					$class,
					$codename,
				], $permission->members([
					'id',
					'title',
					'class',
					'options',
				]));
			}
		}
		$this->render_format($perms);
		return 0;
	}
}
