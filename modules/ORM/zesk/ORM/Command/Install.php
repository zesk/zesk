<?php
declare(strict_types=1);
/**
 * Run through all classes and ensure they are installed correctly
 * Walks through the dependencies of classes using ORM::dependencies and ensures all 'requires' values are
 * included as well.
 * Takes a command parameter afterwards which is the application class to instantiate
 *
 * @category Management
 */
namespace zesk\ORM\Command;

use zesk\Application;
use zesk\Command\SimpleCommand;
use zesk\Hookable;
use zesk\ORM\ORMBase;
use zesk\ORM\Schema;

/**
 * Install all application classes by running their installation code
 * @category BETA - Management
 */
class Install extends SimpleCommand
{
	protected array $shortcuts = ['install', 'in'];

	protected array $option_types = [];

	public function run(): int
	{
		/* @var $application Application */
		$application = $this->application;

		/*
		 * Load the classes for the appliation, and create all of the objects Check to make sure all database schemas
		 * are up to date. If not, exit.
		 */
		$classes = $application->ormModule()->ormClasses();
		$objects_by_class = [];
		$errors = [];
		while (count($classes) > 0) {
			$class = array_shift($classes);
			$objects_by_class[$class] = $object = $this->application->ormFactory($class);
			if (!$object instanceof ORMBase) {
				$this->application->logger->error('{class} is not instance of ORM', [
					'class' => $class,
				]);

				continue;
			}
			$result = Schema::update_object($object);
			if (count($result) > 0) {
				var_dump($result);
				$errors[] = $class;
			}
			$dependencies = $object->dependencies();
			$requires = $dependencies['requires'] ?? [];
			foreach ($requires as $require) {
				if ($objects_by_class[$require] ?? null) {
					continue;
				}
				$classes[] = $require;
			}
		}
		if (count($errors) > 0) {
			fwrite(STDERR, "Schema_Outdated: Schema is not up to date\n");
			echo implode("\n", $errors) . "\n";
			return 1;
		}

		/*
		 * Now, reorder the classes based on dependencies within them.
		 */
		foreach ($objects_by_class as $class => $object) {
			$dependencies = $object->dependencies();
			$install_after = $dependencies['install_after'] ?? [];
			$install_before = $dependencies['install_before'] ?? [];
			$conflicts = $dependencies['conflicts'] ?? [];
			$requires = $dependencies['requires'] ?? [];
			$requires = toList($requires);
			foreach ($requires as $require_class) {
				$require = $objects_by_class[$require_class] ?? null;
				if (!$require) {
					$errors[] = "Require_Class:$require_class required by $class";
				}
			}

			$conflicts = toList($conflicts);
			foreach ($conflicts as $conflict) {
				if (array_key_exists($conflict, $objects_by_class)) {
					$errors[] = "$class conflicts with $conflict";
				}
			}

			/**
			 * Install $class before $before list
			 */
			foreach ($install_before as $before_class) {
				if (array_key_exists($before_class, $objects_by_class)) {
					$objects_by_class[$before_class]->optionAppend('install_next', $object);
				}
			}
			foreach ($install_after as $after_class) {
				if (array_key_exists($after_class, $objects_by_class)) {
					$objects_by_class[$after_class]->optionAppend('install_prev', $object);
				}
			}
			$object->setOption('installed_tag', false);
		}

		if (count($errors) > 0) {
			fwrite(STDERR, "ORM_Errors: Conflicts and errors found\n");
			echo implode("\n", $errors) . "\n";
			return 1;
		}

		$ordered_objects = [];
		foreach ($objects_by_class as $object) {
			$errors = $this->order_walk_object($object, $ordered_objects);
		}
		if (count($errors) > 0) {
			fwrite(STDERR, "ORM_Cycle: ORM instllation order can not be resolved\n");
			echo implode("\n", $errors) . "\n";
			return 1;
		}

		foreach ([
			'pre_install',
			'install',
			'post_install',
		] as $method_name) {
			$application->callHook($method_name . '_begin');
			foreach ($ordered_objects as $object) {
				if (method_exists($object, $method_name)) {
					if ($object instanceof Hookable) {
						$object->callHook($method_name);
					}
					if ($this->optionBool('verbose')) {
						echo "== $object $method_name\n";
					}
					$object->$method_name();
				}
			}
			$application->callHook($method_name . '_end');
		}
		return 0;
	}

	public function order_walk_object(ORMBase $object, array &$ordered_objects)
	{
		if ($object->option('installed_tag')) {
			return true;
		}
		if ($object->option('installed_cyclic')) {
			return [
				$object::class,
			];
		}
		$errors = [];
		$object->setOption('installed_cyclic', true);
		$object_list = $object->option('install_prev');
		$object->setOption('install_prev');
		if (is_array($object_list)) {
			foreach ($object_list as $o) {
				$errors = array_merge($errors, $this->order_walk_object($o));
				if (count($errors) > 0) {
					return $errors;
				}
			}
		}

		$ordered_objects[] = $object;
		$object->setOption('installed_tag', true);

		$object_list = $object->option('install_next');
		$object->setOption('install_next');
		if (is_array($object_list)) {
			foreach ($object_list as $o) {
				$errors = array_merge($errors, $this->order_walk_object($o));
				if (count($errors) > 0) {
					return $errors;
				}
			}
		}
		$object->setOption('installed_cyclic', false);
		return $errors;
	}
}
