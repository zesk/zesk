<?php declare(strict_types=1);
/**
 *
 */
namespace zesk\ORM;

use zesk\ArrayTools;
use zesk\Command_Base;
use zesk\Date;
use zesk\Time;
use zesk\Timestamp;

/**
 * Generate doccomment at-property lists for any ORM/Class_Base pair in the system
 *
 * @category ORM Module
 * @author kent
 */
class Command_ClassProperties extends Command_Base {
	/**
	 *
	 * @var array
	 */
	protected array $option_types = [
		'*' => 'string',
	];

	protected array $shortcuts = ['class-properties', 'cp'];

	/**
	 *
	 * @var array
	 */
	public static array $types_map = [
		Class_Base::TYPE_SERIALIZE => 'array',
		Class_Base::TYPE_ID => 'string',
		Class_Base::TYPE_CREATED => Timestamp::class,
		Class_Base::TYPE_MODIFIED => Timestamp::class,
		Class_Base::TYPE_TIMESTAMP => Timestamp::class,
		Class_Base::TYPE_TIME => Time::class,
		Class_Base::TYPE_DATE => Date::class,
		Class_Base::TYPE_HEX => 'string',
		Class_Base::TYPE_TEXT => 'string',
		Class_Base::TYPE_ID => 'integer',
	];

	/**
	 *
	 * @return string[]
	 */
	private function all_classes(): array {
		return ArrayTools::extract($this->application->orm_module()->all_classes(), null, 'class');
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Command::run()
	 */
	public function run(): int {
		$classes = [];
		while ($this->hasArgument()) {
			$arg = $this->getArgument('class');
			if ($arg === 'all') {
				$classes = array_merge($classes, $this->all_classes());
			} else {
				$classes[] = $arg;
			}
		}
		foreach ($classes as $class) {
			$class_object = $this->application->class_ormRegistry($class);
			if (!$class_object) {
				$this->error("No such class $class_object");

				continue;
			}
			$result = [];
			foreach ($class_object->column_types as $name => $type) {
				$type = self::$types_map[$type] ?? $type;
				$result[$name] = "@property $type \$$name";
			}
			foreach ($class_object->has_one as $name => $type) {
				$result[$name] = "@property \\$type \$$name";
			}
			echo "/**\n * @see " . $class_object::class . "\n" . ArrayTools::joinWrap($result, ' * ', "\n") . " */\n\n";
		}
		return 0;
	}
}
