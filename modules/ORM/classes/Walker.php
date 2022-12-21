<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage orm
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */

/**
 * @author kent
 */

namespace zesk\ORM;

use Psr\Log\LoggerInterface;
use zesk\ArrayTools;
use zesk\StringTools;

/**
 * Traverse ORM objects to convert into various output formats
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
	private array $resolve_methods = [];

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
	protected $preprocess_hook = 'walk';

	/**
	 * Hook called on ORM class and object after walked
	 * @var string
	 */
	protected $postprocess_hook = 'walked';

	public function variables(): array {
		return [
			'include_members' => $this->include_members(), 'exclude_members' => $this->exclude_members(),
			'resolve_methods' => $this->resolve_methods(), 'members_handler' => array_keys($this->members_handler),
			'resolve_objects' => $this->resolve_objects(),
		];
	}

	/**
	 *
	 * @return self
	 */
	public static function factory() {
		return new self();
	}

	/**
	 * Create a new one of what I am
	 *
	 * @return \zesk\ORM\Walker
	 */
	public function child() {
		return self::factory()->inherit($this);
	}

	/**
	 * Inherit settings from another Walker
	 *
	 * @param self $from
	 * @return self
	 */
	public function inherit(self $from) {
		return $this->class_info($from->class_info())->skip_null($from->skip_null())->resolve_methods($from->resolve_methods());
	}

	/**
	 * Getter/setter for depth
	 * @param int $set
	 * @return integer|self
	 */
	public function depth($set = null) {
		if ($set === null) {
			return $this->depth;
		}
		$this->depth = intval($set);
		return $this;
	}

	/**
	 * Getter/setter for class info
	 * @param boolean $set
	 * @return boolean|self
	 */
	public function class_info($set = null) {
		if ($set === null) {
			return $this->class_info;
		}
		$this->class_info = toBool($set);
		return $this;
	}

	/**
	 * Getter/setter for skip null
	 * @param boolean $set
	 * @return boolean|self
	 */
	public function skip_null($set = null) {
		if ($set === null) {
			return $this->skip_null;
		}
		$this->skip_null = toBool($set);
		return $this;
	}

	/**
	 * Getter/setter for members to explicitly include in output
	 *
	 * The default value is null. To set it back to null pass in an empty array.
	 *
	 * @param array $members
	 * @param boolean $append
	 * @return array|null|self
	 */
	public function include_members(array $members = null, $append = false) {
		return $this->_get_set_unique($this->include_members, $members, $append, true);
	}

	/**
	 * Getter/setter for members to explicitly exclude in output
	 *
	 * The default value is null. To set it back to null pass in an empty array.
	 *
	 * @param array $members
	 * @param boolean $append
	 * @return array|null|self
	 */
	public function exclude_members(array $members = null, $append = false) {
		return $this->_get_set_unique($this->exclude_members, $members, $append, false);
	}

	/**
	 * Getter/setter for resolution methods in objects to generate JSON. It uses the first one found.
	 *
	 * The default value is the one method "json".
	 *
	 * @param array $members
	 * @param boolean $append
	 * @return array|null|self
	 */
	public function resolve_methods(array $methods = null, $prepend = false) {
		return $this->_get_set_list($this->resolve_methods, $methods, false, $prepend);
	}

	/**
	 *
	 * @param array $handlers
	 * @param string $append
	 * @return array|\zesk\ORM\JSONWalker
	 */
	public function members_handler(array $handlers = null, $append = false) {
		return $this->_get_set_object($this->members_handler, $handlers, $append);
	}

	/**
	 * List of member dotted paths to resolve in JSON
	 *
	 * @param array $handlers
	 * @param string $append
	 * @return array|\zesk\ORM\JSONWalker
	 */
	public function resolve_objects(array $resolve_objects = null, $append = false) {
		return $this->_get_set_unique($this->resolve_objects, $resolve_objects, $append);
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
	 * @param array $handlers
	 * @param string $append
	 * @return array|\zesk\ORM\JSONWalker
	 */
	public function allow_resolve_objects(array $allow_resolve_objects = null, $append = false) {
		return $this->_get_set_unique($this->allow_resolve_objects, $allow_resolve_objects, $append, true);
	}

	/**
	 * Convert an ORM into an array suitable to serialize into a variety of formats. Has recursion and
	 * specific resolution options for complex structures in the database.
	 *
	 * @param ORMBase $orm
	 */
	public function walk(ORMBase $orm) {
		if ($this->preprocess_hook) {
			$orm->class_orm()->callHookArguments($this->preprocess_hook, [
				$this,
			], null, null, null);
			$orm->callHookArguments($this->preprocess_hook, [
				$this,
			], null, null, null);
		}
		$result = $this->_walk($orm);

		if ($this->postprocess_hook) {
			$result = $orm->callHookArguments($this->postprocess_hook, [
				$result, $this,
			], $result, null, null);
			$result = $orm->class_orm()->callHookArguments($this->postprocess_hook, [
				$result, $this,
			], $result, null, null);
		}
		return $result;
	}

	/**
	 * Getter setter for a unique, unordered list of values to be saved herein.
	 *
	 * @param array $member Reference to $this->member
	 * @param array $list Items to add or set in the list
	 * @param string $append True to append to the list
	 * @param string $allow_null True to allow empty/null lists
	 * @return self|array
	 */
	private function _get_set_unique(&$member, array $list = null, $append = false, $allow_null = false) {
		if ($list === null) {
			return $allow_null ? ($member === null ? null : array_keys($member)) : array_keys($member);
		}
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
		return $this;
	}

	/**
	 * Handle ordered list of non-unique items
	 *
	 * @param array $member
	 * @param array $list
	 * @param boolean $append
	 * @param boolean $prepend
	 * @return array|self
	 */
	private function _get_set_list(&$member, array $list = null, $append = false, $prepend = false) {
		if ($list === null) {
			return $member;
		}
		if ($append === true) {
			$member = array_merge($member, $list);
		} elseif ($prepend === true) {
			$member = array_merge($list, $member);
		} else {
			$member = $list;
		}
		return $this;
	}

	/**
	 * Handle ordered list of unique items (name/value pairs)
	 *
	 * @param array $member
	 * @param array $list
	 * @param boolean $append
	 * @param boolean $prepend
	 * @return array|self
	 */
	private function _get_set_object(&$member, array $object = null, $append = false, $prepend = false) {
		if ($object === null) {
			return $member;
		}
		if ($append === true) {
			$member += $object;
		} else {
			$member = $object;
		}
		return $this;
	}

	/**
	 *
	 * @param LoggerInterface $logger
	 * @return array
	 */
	private function process_resolve_objects(LoggerInterface $logger): array {
		$allow_resolve_objects = $this->allow_resolve_objects();

		$resolve_object_match = [];

		foreach ($this->resolve_objects() as $member_path) {
			if (is_array($allow_resolve_objects) && count($allow_resolve_objects) !== 0 && !StringTools::begins($allow_resolve_objects, $member_path)) {
				$logger->warning('Not allowed to traverse {member_path} as it is not included in {allow_resolve_objects}', compact('allow_resolve_objects', 'member_path'));
			} else {
				[$member, $remaining_path] = pair($member_path, '.', $member_path);
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
	 * @param ORMBase $orm
	 */
	private function _walk(ORMBase $orm) {
		/* Convert to JSONable structure */
		$class_data = $this->class_info ? [
			'_class' => get_class($this), '_parent_class' => get_parent_class($this),
			'_primary_keys' => $orm->members($orm->primary_keys()),
		] : [];
		if ($this->depth === 0) {
			$id = $orm->id();
			if (is_scalar($id) && $this->class_info) {
				return [
					$orm->idColumn() => $id,
				] + $class_data;
			}
			return $id;
		}

		$logger = $orm->application->logger;

		$members = [];
		/* Handle "resolve_objects" list and "allow_resolve_objects" checks */
		$resolve_object_match = $this->process_resolve_objects($logger);
		/* Copy things to JSON */
		$exclude_members = $this->exclude_members; // Yes, we want the keys => true version
		$include_members = $this->include_members();
		if (empty($include_members)) {
			$include_members = null;
		}
		foreach ($orm->members($include_members) as $member => $value) {
			if (array_key_exists($member, $exclude_members)) {
				continue;
			}
			$result = $this->_walk_member($orm, $member, $value, $resolve_object_match, $logger);
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
	 * JSONify a single member
	 *
	 * @param ORMBase $orm
	 * @param string $member
	 * @param mixed $value
	 * @param LoggerInterface $logger
	 * @return unknown
	 */
	private function _walk_member(ORMBase $orm, string $member, $value, array $resolve_object_match, LoggerInterface
	$logger): int|string|null|ORM|Timestamp {
		$handler = $this->members_handler[$member] ?? null;
		if (is_callable($handler) || function_exists($handler)) {
			return $handler($value, $orm, $this);
		}
		// Inherit depth -1, and resolve_methods
		$child_options = $this->child()->depth($this->depth - 1)->resolve_methods($this->resolve_methods());
		if (array_key_exists($member, $resolve_object_match)) {
			try {
				$value = $orm->get($member);
			} catch (Exception_ORMEmpty $e) {
				$value = null;
			} catch (Exception_ORMNotFound $e) {
				$value = null;
			}
			$child_options->resolve_objects($resolve_object_match[$member]);
			// We null out "allow_resolve_objects" as those were checked once, above and are not necessary
			$child_options->allow_resolve_objects([]);
			// Reset the depth to override depth restrictions above
			// Override above depth as we are traversing along the specified path
			$child_options->depth(1);
		}
		if ($value === null) {
			return $value;
		} elseif (is_scalar($value)) {
			return $value;
		} elseif (is_object($value)) {
			return $this->resolve_object($orm, $member, $value, $child_options, $logger);
		} else {
			return null;
		}
	}

	/**
	 * Convert an object
	 * @param ORMBase $object
	 * @param string $member
	 * @param object $value
	 * @param Walker $child_options
	 * @param LoggerInterface $logger
	 * @return mixed
	 */
	private function resolve_object(
		ORMBase $object,
		string $member,
		mixed $value,
		Walker $child_options,
		LoggerInterface $logger
	): string {
		foreach ($this->resolve_methods as $resolve_method) {
			if (is_string($resolve_method) && method_exists($value, $resolve_method)) {
				return $value->$resolve_method($child_options);
			}
			if (is_callable($resolve_method)) {
				return $resolve_method($object, $member, $value, $child_options);
			}
			$logger->warning('Invalid resolve method passed into {class} walker: {type}', [
				'class' => $object::class, 'type' => type($resolve_method),
			]);
		}
		return $value->__toString();
	}
}
