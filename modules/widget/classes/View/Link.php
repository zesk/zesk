<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage widgets
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 * Created on Sun Apr 04 21:18:06 EDT 2010 21:18:06
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class View_Link extends View_Text {
	public function format($set = null) {
		if ($set !== null) {
			return $this->setOption('format', $set);
		}
		return $this->option('format');
	}

	public function action($set = null) {
		if ($set !== null) {
			return $this->setOption('action', $set);
		}
		return $this->option('action');
	}

	public function href($set = null) {
		if ($set !== null) {
			return $this->setOption('href', $set);
		}
		return $this->option('href');
	}

	public function render(): string {
		$object = $this->object;
		$pp = $this->option('format', null);
		if ($pp === null) {
			$pp = parent::render($object);
		}
		$text = $object->applyMap($pp);
		$href = $this->option('href', '');
		if (empty($href)) {
			$action = $this->option('action');
			$href = $this->application->router()->get_route($action, $object);
		} else {
			$href = $object->applyMap($href);
		}
		$is_empty = false;
		if (empty($text)) {
			$is_empty = true;
			$text = $this->empty_string();
		} elseif (!$this->optionBool('html')) {
			$text = htmlspecialchars($text);
		}
		$result = '';
		if ($is_empty && $this->optionBool('empty_no_link', false)) {
			return $text;
		}
		$attrs = $object->applyMap($this->options_include('target;class;onclick;title;id'));
		$add_ref = $this->option('add_ref', URL::queryKeysRemove($this->request->uri(), 'message'));
		if ($add_ref) {
			$href = URL::queryFormat(URL::queryKeysRemove($href, 'ref'), [
				'ref' => $add_ref,
			]);
		}
		$attrs['href'] = $href;
		$attrs['title'] = avalue($attrs, 'title', $href);
		$show_size = $this->showSize();
		$text = $show_size > 0 ? HTML::ellipsis($text, $this->showSize(), $this->option('ellipsis', '&hellip;')) : $text;
		$result = HTML::tag('a', $attrs, $text);
		return $result;
	}
}
