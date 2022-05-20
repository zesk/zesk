<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Preference_Type_Test extends Test_Unit {
	protected array $load_modules = [
		'Preference',
		'MySQL',
	];

	protected function initialize(): void {
		$this->schema_synchronize(Preference_Type::class);
	}

	public function test_object(): void {
		$x = $this->application->model_factory(Preference_Type::class);

		$code_name = 'Poore';
		$name = false;
		Preference_Type::register_name($this->application, $code_name, $name);
	}
}
