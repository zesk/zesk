<?php declare(strict_types=1);
/**
 *
 */
namespace zesk\Subversion;

use zesk\Directory;
use zesk\Exception_System;
use zesk\Configure\Engine;

/**
 *
 * @author kent
 *
 */
class Module extends \zesk\Module_Repository {
	/**
	 * The type of `zesk\Repository`
	 *
	 * @var string
	 */
	public const TYPE = 'svn';

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Module::initialize()
	 */
	public function initialize(): void {
		$required_class = 'SimpleXMLElement';
		if (!class_exists($required_class, false)) {
			throw new Exception_System('{class} requires the {required_class}. See {help_url}', [
				'class' => get_class($this),
				'required_class' => $required_class,
				'help_url' => 'http://php.net/manual/en/simplexml.installation.php',
			]);
		}
		parent::initialize();
		$this->registerRepository(Repository::class, [
			self::TYPE,
			'subversion',
		]);
		$this->application->hooks->add(Engine::class . '::command_subversion', [
			$this,
			'command_subversion',
		]);
	}

	/**
	 * Support configuration command for subversion
	 *
	 * @see Engine
	 * @param Engine $engine
	 */
	public function command_subversion(Engine $engine, array $arguments, $command_name) {
		$app = $engine->application;
		$locale = $app->locale;
		$url = array_shift($arguments);
		$target = $this->application->paths->expand(array_shift($arguments));
		$__ = compact('url', 'target');

		try {
			if (!is_dir($target)) {
				if (!$engine->prompt_yes_no($locale->__('Create subversion directory {target} for {url}', $__))) {
					return false;
				}
				if (!Directory::create($target)) {
					$engine->error($locale->__('Unable to create {target}', $__));
					return false;
				}
				$engine->verboseLog('Created {target}', $__);
			}
			$repo = Repository::factory($this->application, self::TYPE, $target);
			$repo->url($url);
			if ($repo->need_commit()) {
				$engine->log('Repository at {target} has uncommitted changes', $__);
				$engine->log(array_keys($repo->status()));
			}
			if (!$repo->needUpdate()) {
				return null;
			}
			if (!$engine->prompt_yes_no($locale->__('Update subversion {target} from {url}', $__))) {
				return false;
			}
			$engine->log($repo->update());
			return true;
		} catch (\Exception $e) {
			$engine->error('Command failed: {e}', compact('e'));
			return false;
		}
	}
}
