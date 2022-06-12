<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 *            Created on Tue Jul 15 16:28:30 EDT 2008
 */

namespace zesk;

/**
 * Edit an object with multiple columns
 *
 * @author kent
 * @see Control
 * @see Widget::execute
 */
class Control_Edit extends Control {
	/**
	 *
	 * @var string
	 */
	public const option_duplicate_message = 'duplicate_message';

	/**
	 * Options to create the object we're listing, per row
	 *
	 * @var array
	 */
	protected array $class_options = [];

	/**
	 * Header theme
	 *
	 * @var string|array
	 */
	protected string|array $theme_prefix = [];

	/**
	 * Header theme
	 *
	 * @var string
	 */
	protected string|array $theme_header = [];

	/**
	 * Row theme
	 *
	 * @var string[]
	 */
	protected string|array $theme_row = [];

	/**
	 * Layout theme with replacement variables for widget renderings
	 *
	 * @var string[]
	 */
	protected string|array $theme_widgets = [];

	/**
	 * Footer theme
	 *
	 * @var string[]
	 */
	protected string|array $theme_footer = [];

	/**
	 * Suffix theme
	 *
	 * @var string[]
	 */
	protected string|array $theme_suffix = [];

	/**
	 * Row tag
	 */
	protected string $form_tag = 'form';

	/**
	 * Row attributes
	 */
	protected array $form_attributes = [
		'class' => 'edit',
		'method' => 'post',
		'role' => 'form',
	];

	/**
	 *
	 * @var array
	 */
	protected array $widgets = [];

	/**
	 * Cell tag
	 *
	 * @var array
	 */
	protected string $widget_tag = 'div';

	/**
	 * Cell attributes
	 *
	 * @var array
	 */
	protected array $widget_attributes = [
		'class' => 'form-group',
	];

	/**
	 * Label attributes
	 *
	 * @var array
	 */
	protected array $label_attributes = [];

	/**
	 * String
	 *
	 * @var array
	 */
	protected array $widget_wrap_tag = [];

	/**
	 * Optional wrap attributes for each widget
	 *
	 * @var array
	 */
	protected array $widget_wrap_attributes = [];

	/**
	 * Fields to preserve in the form from the request
	 *
	 * @var array
	 */
	protected array $form_preserve_hidden = [
		'ajax',
		'ref',
	];

	/**
	 * Lazy evaluate the class based on this object's class name (if not set)
	 *
	 * @return string
	 */
	private function _class(): string {
		if ($this->class === '') {
			throw new Exception_Semantics('{class}::$class member must be set and is not.', [
				'class' => get_class($this),
			]);
		}
		return $this->class;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Widget::model()
	 */
	public function model(): Model {
		return $this->modelFactory($this->_class());
	}

	/**
	 *
	 * @param array $ww
	 * @return array
	 */
	private function _filter_widgets(array $ww): array {
		if ($this->hasOption('widgets_filter')) {
			$this->application->deprecated('{class} has deprecated widgets_filter option, use widgets_include only 2017-11');
		}
		$filter = $this->optionIterable('widgets_include', $this->optionIterable('widgets_filter'));
		$exclude = $this->optionIterable('widgets_exclude', null);
		foreach ($ww as $i => $w) {
			$col = $w->column();
			if (count($filter) > 0 && !in_array($col, $filter)) {
				unset($ww[$i]);
			}
			if (in_array($col, $exclude)) {
				unset($ww[$i]);
			}
		}
		return $ww;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Widget::initialize($object)
	 */
	protected function initialize(): void {
		if (!$this->name()) {
			$this->name('edit');
		}
		$ww = $this->call_hook('widgets');
		if (is_array($ww)) {
			$ww = $this->_filter_widgets($ww);
			$this->children($ww);
		}
		parent::initialize();

		$this->initialize_theme_paths();

		$this->form_attributes['action'] = $this->request->path();

		$this->form_attributes = HTML::addClass($this->form_attributes, strtr(strtolower(get_class($this)), '_', '-'));
		if ($this->parent && $this->traverse === null) {
			$this->traverse = true;
			if ($this->parent instanceof Control_Edit) {
				$this->form_tag = '';
			}
		}
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Widget::validate()
	 */
	public function validate(): bool {
		if ($this->request->get('delete') !== null && $this->userCan('delete', $this->object)) {
			return true;
		}
		return parent::validate();
	}

	/**
	 *
	 * @return boolean
	 */
	protected function delete_redirect(): bool {
		$redirect = $this->options['delete_redirect'] ?? null;
		$vars = ArrayTools::prefixKeys($this->object->variables(), 'object.') + ArrayTools::prefixKeys($this->request->variables(), 'request.');
		$url = null;
		if ($redirect) {
			$redirect = map($redirect, $vars);
			$url = URL::queryFormat($redirect, 'ref', $this->request->get('ref', $this->request->url()));
		}
		$message = map($this->option('delete_redirect_message'), $vars);
		$response = $this->response();
		if ($this->preferJSON()) {
			$response->json()->data([
				'result' => true,
				'message' => $message,
				'redirect' => $url,
				'object' => $this->object->json([
					'depth' => 0,
				]),
			]);
			// Stop processing submit
			return false;
		}
		if (!$redirect) {
			return true;
		}

		throw new Exception_Redirect($url, $message);
	}

	/**
	 * @return string
	 */
	public function duplicate_message(): string {
		return $this->_get_duplicate_message();
	}

	/**
	 * @return string
	 */
	public function duplicateMessage(): string {
		return $this->_get_duplicate_message();
	}

	public function setDuplicateMessage(string $set) {
		return $this->setOption(self::option_duplicate_message, $set);
	}

	private function _get_duplicate_message() {
		$message = $this->option(self::option_duplicate_message, 'Another {_class_name} with the same name already exists.');
		$message = $this->call_hook_arguments('duplicate_message', [
			$message,
		], $message);
		$message = __($message, $this->object->variables());
		return $message;
	}

	/**
	 * @return bool
	 * @throws \Exception
	 */
	public function submit_store(): bool {
		try {
			if (!$this->object->store()) {
				$this->error($this->option('store_error', __('Unable to save {object}', [
					'object' => strval($this->object),
				])));
				$this->call_hook('store_failed');
				return true;
			}
			$this->call_hook('stored');
		} catch (Database_Exception_Duplicate $dup) {
			$this->error($this->_get_duplicate_message());
			return $this->call_hook_arguments('store_failed', [], false);
		} catch (Exception_ORM_Duplicate $dup) {
			$this->error($this->_get_duplicate_message());
			return $this->call_hook_arguments('store_failed', [], false);
		}
		return true;
	}

	/**
	 * @return bool
	 * @throws Exception_Redirect
	 */
	protected function submit_handle_delete(): bool {
		if ($this->request->get('delete') && $this->userCan('delete', $this->object)) {
			$this->object->delete();
			return $this->delete_redirect();
		}
		// Continue
		return true;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Widget::submit()
	 */
	public function submit(): bool {
		if (!$this->submit_handle_delete()) {
			return false;
		}
		if (!$this->submit_children()) {
			return false;
		}
		if (!$this->submit_store()) {
			return false;
		}
		return $this->submit_redirect();
	}

	/**
	 * Set up theme paths
	 *
	 * @return void
	 */
	protected function initialize_theme_paths(): void {
		$hierarchy = $this->application->classes->hierarchy($this, __CLASS__);
		foreach ($hierarchy as $index => $class) {
			$hierarchy[$index] = strtr(strtolower($class), [
					'_' => '/',
					'\\' => '/',
				]) . '/';
		}
		// Set default theme to control/foo/prefix, control/foo/header etc.
		foreach (to_list('prefix;header;footer;suffix') as $var) {
			$theme_var = "theme_$var";
			$debug_type = 'overridden';
			if (!$this->$theme_var) {
				$this->$theme_var = ArrayTools::suffixValues($hierarchy, $var);
				$debug_type = 'default';
			}
			if ($this->optionBool('debug_theme_paths')) {
				$this->application->logger->debug('{class}->{theme_var} theme ({debug_type}) is {paths}', [
					'debug_type' => $debug_type,
					'class' => get_class($this),
					'theme_var' => $theme_var,
					'paths' => $this->$theme_var,
				]);
			}
		}
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Widget::themeVariables()
	 */
	public function themeVariables(): array {
		$enctype = $this->form_attributes['enctype'] ?? null;
		if ($enctype === null && $this->upload()) {
			// TODO - why is this added here? side effects
			$this->form_attributes['enctype'] = 'multipart/form-data';
		}
		return [
				'widgets' => $this->children(),
				'theme_prefix' => $this->theme_prefix,
				'theme_suffix' => $this->theme_suffix,
				'theme_header' => $this->theme_header,
				'theme_row' => $this->theme_row,
				'theme_footer' => $this->theme_footer,
				'form_tag' => $this->form_tag,
				'form_attributes' => $this->form_attributes,
				'label_attributes' => $this->label_attributes,
				'widget_tag' => $this->widget_tag,
				'widget_attributes' => $this->widget_attributes,
				'widget_wrap_tag' => $this->widget_wrap_tag,
				'widget_wrap_attributes' => $this->widget_wrap_attributes,
				'nolabel_widget_wrap_attributes' => $this->nolabel_widget_wrap_attributes ?? $this->widget_wrap_attributes,
				'form_preserve_hidden' => $this->form_preserve_hidden,
				'theme_widgets' => $this->theme_widgets,
				'title' => $this->title(),
			] + parent::themeVariables() + $this->options;
	}
}
