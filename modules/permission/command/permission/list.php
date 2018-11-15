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
        $this->render_format($perms);
        return 0;
    }
}
