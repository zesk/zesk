<?php
namespace zesk;

/**
 * List all permissions available in the system
 *
 * @author kent
 * @category Permission
 * @see Module_Permission
 */
class Command_Permission_List extends Command_Base {
	public $option_types = array(
		'format' => 'string',
	);

	public function run() {
		$perms = $this->application->modules->object("permission")->permissions();
		foreach ($perms['class'] as $class => $actions) {
			foreach ($actions as $codename => $permission) {
				assert($permission instanceof Permission);
				apath_set($perms, array(
					"class",
					$class,
					$codename,
				), $permission->members(array(
					"id",
					"title",
					"class",
					"options",
				)));
			}
		}
		$this->render_format($perms);
		return 0;
	}
}
