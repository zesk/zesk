<?php

/**
 * Run through all classes and ensure they are installed correctly
 * Walks through the dependencies of classes using ORM::dependencies and ensures all 'requires' values are
 * included as well.
 * Takes a command parameter afterwards which is the application class to instantiate
 * @global boolean debug.db Turn database debugging on or off
 * @global boolean verbose Output each object and method as it is invoked
 * @global string Application::class Set this to the application class to install (if not the default)
 * @category Management
 */
namespace zesk;

/**
 * Install all application classes by running their installation code
 */
class Command_Install extends Command_Base {
	protected $option_types = array();
	function run() {
		/* @var $application Application */
		$application = $this->application;
		
		/*
		 * Load the classes for the appliation, and create all of the objects Check to make sure all database schemas
		 * are up to date. If not, exit.
		 */
		$classes = $application->orm_classes();
		$objects_by_class = array();
		$errors = array();
		while (count($classes) > 0) {
			$class = array_shift($classes);
			$objects_by_class[$class] = $object = $this->application->orm_factory($class);
			if (!$object instanceof ORM) {
				$this->application->logger->error("{class} is not instance of ORM", array(
					"class" => $class
				));
				continue;
			}
			$result = Database_Schema::updateORM($object);
			if (count($result) > 0) {
				var_dump($result);
				$errors[] = $class;
			}
			$dependencies = $object->dependencies();
			$requires = avalue($dependencies, 'requires', array());
			foreach ($requires as $require) {
				if (avalue($objects_by_class, $require)) {
					continue;
				}
				$classes[] = $require;
			}
		}
		if (count($errors) > 0) {
			fwrite(STDERR, "Schema_Outdated: Schema is not up to date\n");
			echo implode("\n", $errors) . "\n";
			exit(1);
		}
		
		/*
		 * Now, reorder the classes based on dependencies within them.
		 */
		$objects = array();
		$befores = array();
		$afters = array();
		foreach ($objects_by_class as $class => $object) {
			$requires = $conflicts = $install_before = $install_after = array();
			$dependencies = $object->dependencies();
			extract($dependencies, EXTR_IF_EXISTS);
			
			$requires = to_list($requires);
			foreach ($requires as $require_class) {
				$require = avalue($objects_by_class, $require_class);
				if (!$require) {
					$errors[] = "Require_Class:$require_class required by $class";
				}
			}
			
			$conflicts = to_list($conflicts);
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
					$objects_by_class[$before_class]->option_append_list('install_next', $object);
				}
			}
			foreach ($install_after as $after_class) {
				if (array_key_exists($after_class, $objects_by_class)) {
					$objects_by_class[$after_class]->option_append_list('install_prev', $object);
				}
			}
			$object->set_option('installed_tag', false);
		}
		
		if (count($errors) > 0) {
			fwrite(STDERR, "ORM_Errors: Conflicts and errors found\n");
			echo implode("\n", $errors) . "\n";
			exit(1);
		}
		
		$ordered_objects = array();
		foreach ($objects_by_class as $object) {
			$errors = $this->order_walk_object($object, $ordered_objects);
		}
		if (count($errors) > 0) {
			fwrite(STDERR, "ORM_Cycle: ORM instllation order can not be resolved\n");
			echo implode("\n", $errors) . "\n";
			exit(1);
		}
		
		foreach (array(
			'pre_install',
			'install',
			'post_install'
		) as $method_name) {
			$application->call_hook($method_name . "_begin");
			foreach ($ordered_objects as $object) {
				if (method_exists($object, $method_name)) {
					if ($object instanceof Hookable) {
						$object->call_hook($method_name);
					}
					if ($this->option_bool('verbose')) {
						echo "== $object $method_name\n";
					}
					$object->$method_name();
				}
			}
			$application->call_hook($method_name . "_end");
		}
	}
	function order_walk_object(ORM $object, array &$ordered_objects) {
		if ($object->option('installed_tag')) {
			return true;
		}
		if ($object->option('installed_cyclic')) {
			return array(
				get_class($object)
			);
		}
		$errors = array();
		$object->set_option('installed_cyclic', true);
		$object_list = $object->option('install_prev');
		$object->set_option('install_prev');
		if (is_array($object_list)) {
			foreach ($object_list as $o) {
				$errore = array_merge($errore, $this->order_walk_object($o));
				if (count($errors) > 0) {
					return $errors;
				}
			}
		}
		
		$ordered_objects[] = $object;
		$object->set_option('installed_tag', true);
		
		$object_list = $object->option('install_next');
		$object->set_option('install_next');
		if (is_array($object_list)) {
			foreach ($object_list as $o) {
				$errore = array_merge($errors, $this->order_walk_object($o));
				if (count($errors) > 0) {
					return $errors;
				}
			}
		}
		$object->set_option('installed_cyclic', false);
		return $errors;
	}
}
