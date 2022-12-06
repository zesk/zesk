<?php
declare(strict_types=1);

namespace zesk;

class TestWidget extends UnitTest {
	protected array $load_modules = [
		'Widget',
	];

	public function widget_tests(Widget $testx): void {
		$column = 'col';
		$label = 'label';
		$name = 'name';
		$testx->names($column, $label, $name);

		$request = new Request($this->application, 'http://localhost/');
		$response = new Response($this->application, $request);

		$object = new Model($this->application);
		$testx->setRequest($request);
		$testx->response($response);

		$testx->execute($object);

		$name = 'form-name';
		$testx->setFormName($name);
		$this->assertEquals($name, $testx->formName());
		$default = 'form';
		$testx->setFormName($default);

		$parent = $testx->widgetFactory('zesk\\Widget');
		$testx->parent($parent);

		$required = true;
		$testx->required($required);

		$testx->required();

		$default = 'no_name';
		$testx->column($default);

		$testx->name();

		$testx->label();

		$testx->is_visible($object);

		$testx->clear();

		$testx->errors();

		$testx->messages();

		$testx->has_errors();

		$sType = 'err';
		$sMessage = null;
		$testx->error($sType, $sMessage);

		$sType = 'mess';
		$sMessage = null;
		$testx->message($sType, $sMessage);

		$mixed = null;
		$testx->error($mixed);

		$testx->error_required();

		$testx->inputAttributes();
		$testx->inputAttributes(['core']);

		$default = 'Hey, dude.';
		$this->assertEquals($testx->empty_string($default)->empty_string(), $default);

		$data = null;
		$testx->suffix($data);

		$data = null;
		$testx->prefix($data);

		$testx->language();

		$testx->locale();

		$testx->showSize();
	}

	/**
	 *
	 */
	public function test_basics(): void {
		$testx = $this->application->widgetFactory('zesk\\Widget');

		$this->widget_tests($testx);
	}
}
