<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage orm
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */

/**
 * @author kent
 */

namespace zesk\Doctrine;

use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionException;
use zesk\ArrayTools;
use zesk\RuntimeException;
use zesk\StringTools;
use zesk\Timestamp;
use zesk\Types;

/**
 * Traverse Model objects to convert into various output formats
 *
 * @author kent
 */
class Walker {
	/**
	 * Current depth to traverse
	 *
	 * @var integer
	 */
	private int $depth = 1;

	/**
	 *
	 * Do not output class information
	 *
	 * @var boolean
	 */
	private bool $class_info = false;

	/**
	 * Skip NULL values in resulting object
	 *
	 * @var boolean
	 */
	private bool $skip_null = false;

	/**
	 * Members to explicitly include. If not supplied, all members.
	 *
	 * @var array
	 */
	private array $include_members = [];

	/**
	 * Members to explicitly exclude. If not supplied, just $members is included.
	 *
	 * @var array
	 */
	private array $exclude_members = [];

	/**
	 * List of methods to call on ORM objects, in order
	 *
	 * @var array
	 */
	protected array $resolve_methods = [];

	/**
	 * Member => function pairs to output members using callbacks
	 *
	 * @var array
	 */
	private array $members_handler = [];

	/**
	 * Unique list of paths of objects to traverse
	 *
	 * @var array
	 */
	private array $resolve_objects = [];

	/**
	 * Unique list of paths of permitted traversal paths
	 *
	 * @var array
	 */
	private array $allow_resolve_objects = [];

	/**
	 * Hook called on ORM class and object before running
	 *
	 * @var string
	 */
	protected string $preprocess_hook = 'walk';

	/**
	 * Hook called on ORM class and object after walked
	 * @var string
	 */
	protected string $postprocess_hook = 'walked';

	public function variables(): array {
		return [
			'include_members' => $this->includeMembers(), 'exclude_members' => $this->excludeMembers(),
			'resolve_methods' => $this->resolveMethods(), 'members_handler' => array_keys($this->members_handler),
			'resolve_objects' => $this->resolveObjects(),
		];
	}

	/**
	 *
	 * @return self
	 */
	public static function factory(): self {
		return new self();
	}

	/**
	 * Create a new one of what I am
	 *
	 * @return Walker
	 */
	public function child(): self {
		return self::factory()->inherit($this);
	}

	/**
	 * Inherit settings from another Walker
	 *
	 * @param self $from
	 * @return self
	 */
	public function inherit(self $from): self {
		return $this->setClassInfo($from->classInfo())->setSkipNull($from->skipNull())->setResolveMethods($from->resolveMethods());
	}

	/**
	 * Setter for depth
	 *
	 * @param int $set
	 * @return self
	 */
	public function setDepth(int $set): self {
		$this->depth = $set;
		return $this;
	}

	/**
	 * Getter for depth
	 *
	 * @return int
	 */
	public function depth(): int {
		return $this->depth;
	}

	/**
	 * Setter for class info
	 * @param bool $set
	 * @return self
	 */
	public function setClassInfo(bool $set): self {
		$this->class_info = $set;
		return $this;
	}

	/**
	 * Getter for class info
	 * @return bool
	 */
	public function classInfo(): bool {
		return $this->class_info;
	}

	/**
	 * @param bool $set
	 * @return $this
	 */
	public function setSkipNull(bool $set): self {
		$this->skip_null = $set;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function skipNull(): bool {
		return $this->skip_null;
	}

	/**
	 * Getter for members to explicitly include in output
	 *
	 * @return array
	 */
	public function includeMembers(): array {
		return array_keys($this->include_members);
	}

	/**
	 * @param string $member
	 * @return bool
	 */
	public function included(string $member): bool {
		if (count($this->include_members) === 0) {
			return true;
		}
		return array_key_exists($member, $this->include_members);
	}

	public function setIncludeMembers(array $members, bool $append = false): self {
		$this->include_members = $this->_set_unique($this->include_members, $members, $append, true);
		return $this;
	}

	/**
	 * Getter for members to explicitly exclude in output
	 *
	 * @return array
	 */
	public function excludeMembers(): array {
		return array_keys($this->exclude_members);
	}

	/**
	 * @param array|null $members
	 * @param bool $append
	 * @return $this
	 */
	public function setExcludeMembers(array $members = null, bool $append = false): self {
		$this->exclude_members = $this->_set_unique($this->exclude_members, $members, $append);
		return $this;
	}

	/**
	 * Getter/setter for resolution methods in objects to generate JSON. It uses the first one found.
	 *
	 * The default value is the one method "json".
	 *
	 * @return array
	 */
	public function resolveMethods(): array {
		return $this->resolve_methods;
	}

	/**
	 * @param array $methods
	 * @return $this
	 */
	public function setResolveMethods(array $methods): self {
		$this->resolve_methods = $methods;
		return $this;
	}

	/**
	 *
	 * @return array
	 */
	public function membersHandler(): array {
		return $this->members_handler;
	}

	/**
	 * @param array $handlers
	 * @param bool $append
	 * @return $this
	 */
	public function setMembersHandler(array $handlers, bool $append = false): self {
		$this->members_handler = $append ? $handlers + $this->members_handler : $handlers;
		return $this;
	}

	/**
	 * List of member dotted paths to resolve in JSON
	 *
	 * @param array $resolve_objects
	 * @param bool $append
	 * @return $this
	 */
	public function setResolveObjects(array $resolve_objects, bool $append = false): self {
		$this->resolve_objects = $this->_set_unique($this->resolve_objects, $resolve_objects, $append);
		return $this;
	}

	public function resolveObjects(): array {
		return $this->resolve_objects;
	}

	/**
	 * A list of permitted path traversals based on the current object. Of the form:
	 *
	 * ["user.account.payment.owner","user.account.product"]
	 *
	 * Consider this the "security" for "resolve_objects" as you can not specify a path outside of these.
	 * It allows for requests (e.g. users) to pass in their own "resolve_objects" and have it validated during
	 * traversal.
	 *
	 * You can permit all resolve_objects paths by setting this to an empty array, so use with caution.
	 *
	 * @param array $allow_resolve_objects
	 * @param bool $append
	 * @return self
	 */
	public function setAllowResolveObjects(array $allow_resolve_objects, bool $append = false): self {
		$this->allow_resolve_objects = $this->_set_unique($this->allow_resolve_objects, $allow_resolve_objects, $append, true);
		return $this;
	}

	/**
	 * @return array
	 */
	public function allowResolveObjects(): array {
		return $this->allow_resolve_objects;
	}

	/**
	 * Convert an ORM into an array suitable to serialize into a variety of formats. Has recursion and
	 * specific resolution options for complex structures in the database.
	 *
	 * @param Model $model
	 * @return int|string|array
	 */
	public function walk(Model $model): int|string|array {
		if ($this->preprocess_hook) {
			$model->invokeHooks($this->preprocess_hook, [
				$model, $this,
			]);
		}
		$result = $this->_walk($model);
		if ($this->postprocess_hook) {
			$result = $model->invokeFilters($this->postprocess_hook, $result, [
				$result, $this,
			], 0);
		}
		return $result;
	}

	private function _set_unique(null|array $member, array $list, bool $append = false, bool $allow_null = false): array {
		if ($allow_null) {
			if (!is_array($member) || $append === false) {
				if (count($list) === 0) {
					$member = null;
				} else {
					$member = ArrayTools::keysFromValues($list, true);
				}
			} else {
				$member += ArrayTools::keysFromValues($list, true);
			}
		} else {
			if ($append === false) {
				$member = ArrayTools::keysFromValues($list, true);
			} else {
				$member += ArrayTools::keysFromValues($list, true);
			}
		}
		return $member;
	}

	/**
	 *
	 * @param LoggerInterface $logger
	 * @return array
	 */
	private function process_resolve_objects(LoggerInterface $logger): array {
		$allow_resolve_objects = $this->allowResolveObjects();

		$resolve_object_match = [];

		foreach ($this->resolveObjects() as $member_path) {
			if (is_array($allow_resolve_objects) && count($allow_resolve_objects) !== 0 && !StringTools::begins($allow_resolve_objects, $member_path)) {
				$logger->warning('Not allowed to traverse {member_path} as it is not included in {allow_resolve_objects}', compact('allow_resolve_objects', 'member_path'));
			} else {
				[$member, $remaining_path] = StringTools::pair($member_path, '.', $member_path);
				if (!array_key_exists($member, $resolve_object_match)) {
					$resolve_object_match[$member] = [];
				}
				if ($remaining_path !== null) {
					$resolve_object_match[$member][] = $remaining_path;
				}
			}
		}

		return $resolve_object_match;
	}

	/**
	 * Convert an ORM into an array suitable to serialize into JSON. Has recursion and
	 * specific resolution options for complex structures in the database.
	 *
	 * @param Model $model
	 * @return int|string|array
	 */
	private function _walk(Model $model): int|string|array {
		/* Convert to JSON structure */
		$class_data = $this->class_info ? [
			'_class' => get_class($this), '_parent_class' => get_parent_class($this),
		] : [];
		$reflectionClass = new ReflectionClass($model);
		if ($this->depth === 0) {
			$ids = $model->application->entityManager()->getClassMetadata($model::class)->getIdentifierColumnNames();

			try {
				if (count($ids) === 1) {
					$idName = ArrayTools::first($ids);
					return $reflectionClass->getProperty($idName);
				}
				$result = [];
				foreach ($ids as $idColumn) {
					$result += [
						$idColumn => $reflectionClass->getProperty($idColumn),
					];
				}
				return $result + $class_data;
			} catch (ReflectionException $e) {
				throw new RuntimeException('Invalid ids: {ids} for {class}', [
					'ids' => $ids, 'class' => $model,
				], $e->getCode(), $e);
			}
		}
		$logger = $model->application->logger();

		$members = [];
		/* Handle "resolve_objects" list and "allow_resolve_objects" checks */
		$resolve_object_match = $this->process_resolve_objects($logger);
		/* Copy things to JSON */
		$exclude_members = $this->excludeMembers(); // Yes, we want the keys => true version
		$include_members = $this->includeMembers();
		if (empty($include_members)) {
			$include_members = null;
		}
		foreach ($reflectionClass->getProperties() as $member => $value) {
			if (!$this->included($member)) {
				continue;
			}
			if (array_key_exists($member, $exclude_members)) {
				continue;
			}
			$result = $this->_walk_member($model, $member, $value, $resolve_object_match, $logger);
			if ($result === null) {
				if (!$this->skip_null) {
					$members[$member] = $result;
				}
			} else {
				$members[$member] = $result;
			}
		}
		return $members;
	}

	/**
	 * JSON a single member
	 *
	 * @param Model $model
	 * @param string $member
	 * @param mixed $value
	 * @param array $resolve_object_match
	 * @param LoggerInterface $logger
	 * @return int|string|null|Model|Timestamp
	 */
	private function _walk_member(Model $model, string $member, mixed $value, array $resolve_object_match, LoggerInterface $logger): int|string|null|Model|Timestamp {
		$handler = $this->members_handler[$member] ?? null;
		if (is_callable($handler) || function_exists($handler)) {
			return $handler($value, $model, $this);
		}
		// Inherit depth -1, and resolve_methods
		$child_options = $this->child()->setDepth($this->depth - 1)->setResolveMethods($this->resolveMethods());
		if (array_key_exists($member, $resolve_object_match)) {
			$reflectionClass = new ReflectionClass($model);

			try {
				$value = $reflectionClass->getProperty($member);
			} catch (ReflectionException $e) {
				throw new RuntimeException('Invalid member {member} for class {class}', [
					'member' => $member, 'class' => $model,
				], $e->getCode(), $e);
			}
			$child_options->setResolveObjects($resolve_object_match[$member]);
			// We null out "allow_resolve_objects" as those were checked once, above and are not necessary
			$child_options->setAllowResolveObjects([]);
			// Reset the depth to override depth restrictions
			// Override above depth as we are traversing along the specified path
			$child_options->setDepth(1);
		}
		if ($value === null) {
			return null;
		}
		if (is_scalar($value)) {
			return $value;
		} else if (is_object($value)) {
			return $this->resolve_object($model, $member, $value, $child_options, $logger);
		} else {
			return null;
		}
	}

	/**
	 * Convert an object
	 * @param Model $object
	 * @param string $member
	 * @param object $value
	 * @param Walker $child_options
	 * @param LoggerInterface $logger
	 * @return mixed
	 */
	private function resolve_object(Model $object, string $member, mixed $value, Walker $child_options, LoggerInterface $logger): string {
		foreach ($this->resolve_methods as $resolve_method) {
			if (is_string($resolve_method) && method_exists($value, $resolve_method)) {
				return $value->$resolve_method($child_options);
			}
			if (is_callable($resolve_method)) {
				return $resolve_method($object, $member, $value, $child_options);
			}
			$logger->warning('Invalid resolve method passed into {class} walker: {type}', [
				'class' => $object::class, 'type' => Types::type($resolve_method),
			]);
		}
		return $value->__toString();
	}
}
