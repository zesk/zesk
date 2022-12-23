<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage widgets
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 * Created on Sun Apr 04 21:32:26 EDT 2010 21:32:26
 *
 * @todo Remove http.inc and fix URL::query_remove, etc.
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class View_Time_Zone extends View_Text {
	public function render(): string {
		$pp = $this->option('format', null);
		if ($pp === null) {
			$pp = parent::render();
		}
		$object = $this->object;
		$text = $object->applyMap($pp);
		$href = $object->applyMap($this->option('href', ''));
		if (empty($text)) {
			$text = $this->empty_string();
		} elseif (!$this->optionBool('HTML')) {
			$text = htmlspecialchars($text);
		}
		$attrs = $object->applyMap($this->options(toList('target;class;onclick')));
		$uri = $this->request->uri();
		$add_ref = $this->option('add_ref', URL::queryKeysRemove($uri, 'message'));
		if ($add_ref) {
			$href = URL::queryFormat(URL::queryKeysRemove($href, 'ref'), [
				'ref' => $add_ref,
			]);
		}
		$attrs['href'] = $href;
		$result = HTML::tag('a', $attrs, HTML::ellipsis($text, $this->option('ShowSize', -1), $this->option('Ellipsis', '...')));
		return $this->render_finish($object, $result);
	}
}
