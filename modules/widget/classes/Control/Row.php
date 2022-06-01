<?php declare(strict_types=1);
namespace zesk;

class Control_Row extends Control {
	/**
	 *
	 * @var string
	 */
	protected $row_tag = null;

	/**
	 * Row theme
	 *
	 * @var string
	 */
	protected $row_attributes = [];

	/**
	 * Get/set the row tag
	 * @param string $row_tag Set to false to have no row tag
	 * @return Widget_Row|mixed
	 */
	public function row_tag($row_tag = null) {
		if ($row_tag !== null) {
			$this->row_tag = $row_tag;
			return $this;
		}
		return $this->row_tag;
	}

	/**
	 * Get/set the row attributes
	 *
	 * @param array $row_attributes
	 * @param boolean $append Append to existing $row_attributes
	 * @return Widget_Row|mixed
	 */
	public function row_attributes(array $row_attributes = null, $append = true) {
		if ($row_attributes !== null) {
			$this->row_attributes = $append ? $row_attributes + $this->row_attributes : $row_attributes;
			return $this;
		}
		return $this->row_attributes;
	}

	/**
	 * {@inheritDoc}
	 * @see Widget::themeVariables()
	 */
	public function themeVariables(): array {
		return [
			'object' => $this->object,
			'row_widget' => $this,
			'row_widgets' => $this->children(),
			'row_tag' => $this->row_tag,
			'row_attributes' => $this->row_attributes,
		] + parent::themeVariables();
	}

	/**
	 * Render row wrapper
	 *
	 * {@inheritDoc}
	 * @see Widget::render()
	 */
	public function render(): string {
		if ($this->row_tag) {
			$object = $this->object;
			$map = $object->variables() + $this->theme_variables;
			return HTML::tag(map($this->row_tag, $map), map($this->row_attributes, $map), parent::render());
		}
		return parent::render();
	}
}
