<?php

/**
 *
 */
namespace zesk\NodeJS;

//use \SplFileInfo;
use zesk\Command;
use zesk\ArrayTools;
use zesk\Logger\Handler;
use zesk\Directory;

/**
 *
 * @author kent
 *
 */
class Module extends \zesk\Module {
	public function hook_build(Command $command) {
		$app = $this->application;
		$node_modules_path = $this->option("node_modules_path", $app->path("node_modules"));
		if (empty($node_modules_path)) {
			$command->error("No node_modules_path configured in {class}", array(
				"class" => __CLASS__,
			));
			return;
		}
		if (!is_dir($node_modules_path)) {
			$command->error("Setting {class}::node_modules_path is not a valid directory ({node_modules_path})", array(
				"class" => __CLASS__,
				"node_modules_path" => $node_modules_path,
			));
			return;
		}
		$result = array();
		foreach (to_list($this->application->modules->load()) as $name => $module) {
			$path = avalue($module, 'path');
			if ($path) {
				$path = path($module['path'], "node_modules");
				if (is_dir($path)) {
					$result += $this->gather_node_modules_paths($path);
				}
			}
			$node_modules_map = apath($module, "configuration.node_modules", array());
			if (is_array($node_modules_map) && count($node_modules_map) > 0 && ArrayTools::is_assoc($node_modules_map)) {
				$result += $this->convert_application_path($command, $node_modules_map, "module $name");
			}
		}
		foreach ($result as $codename => $target_path) {
			$link_path = path($node_modules_path, $codename);
			$args = array(
				"severity" => "info",
				"link_path" => $link_path,
				"target_path" => $target_path,
			);
			if (is_link($link_path)) {
				$existing_target = readlink($link_path);
				if ($existing_target !== $target_path) {
					$command->log("Verified symlink from {target_path} to {link_path}", $args);

					continue;
				}
				if (!unlink($link_path)) {
					$command->error("Can not unlink {link_path}", $args);
				} else {
					if (!symlink($target_path, $link_path)) {
						$command->error("Can not create symlink from {target_path} to {link_path}", $args);
					} else {
						$command->log("{link_path} -> {target_path} configured", array(
							'severity' => 'debug',
						) + $args);
					}
				}
			} elseif (is_file($link_path)) {
				$command->error("File {link_path} is in the way, can not create symlink to {target_path}", $args);
			} elseif (is_dir($link_path)) {
				$command->error("Directory {link_path} is in the way, can not create symlink to {target_path}", $args);
			} else {
				if (symlink($target_path, $link_path)) {
					$command->log("Created symlink from {target_path} to {link_path}", $args);
				} else {
					$command->error("Directory {link_path} is in the way, can not create symlink to {target_path}", $args);
				}
			}
		}
	}

	private function convert_application_path(Handler $handler, array $items, $context = null) {
		$app_path = $this->application->path();
		$map = array(
			"{application_home}" => $app_path,
			"{zesk_home}" => $this->application->zesk_home(),
			/* @deprecated 2018-02 */
			"{application_root}" => $app_path,
			"{zesk_root}" => $this->application->zesk_home(),
		);
		if ($context === null) {
			$context = calling_function();
		}
		$result = array();
		foreach ($items as $codename => $path) {
			$mapped_path = map($path, $map);
			if (!Directory::is_absolute($mapped_path)) {
				$mapped_path = path($app_path, $path);
			}
			if (!is_dir($mapped_path)) {
				$handler->log("{path} (is not a valid path, found in {context}", array(
					"path" => $path,
					"context" => $context,
					"severity" => "error",
				));
			} else {
				$result[$codename] = $mapped_path;
			}
		}
		return $result;
	}

	private function gather_node_modules_paths($path) {
		$iterator = new \DirectoryIterator($path);
		foreach ($iterator as $file) {
			/* @vae $file \SplFileInfo */
			if ($file->isDot()) {
				continue;
			}
			if (!$file->isDir()) {
				continue;
			}
			$name = $file->getFilename();
			$result[$name] = path($path, $name);
		}
		return $result;
	}
}
