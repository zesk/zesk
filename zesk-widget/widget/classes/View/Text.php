<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage widgets
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 * Created on Tue Jul 15 15:59:24 EDT 2008
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class View_Text extends View {
	public function format($set = null) {
		return $set === null ? $this->option('format') : $this->setOption('format', $set);
	}

	public function render(): string {
		$showSize = $this->showSize();
		if ($this->hasOption('value')) {
			$v = $this->option('value');
		} else {
			$v = $this->value();
		}
		if ($v === null) {
			$v = $this->empty_string();
			if (toBool($this->empty_string_no_wrap)) {
				return $v;
			}
		}
		$transform = $this->option('transform');
		$is_html = $this->optionBool('HTML');
		switch ($transform) {
			case 'html':
				$v = htmlspecialchars($v);

				break;
			case 'no-html':
				$v = HTML::strip($v);

				break;
			case 'camel':
				$v = StringTools::capitalize($v);

				break;
		}
		$close_ellip = '';
		$old_v = '';
		$my_id = null;
		if ($showSize > 0) {
			$allow_show_all = $this->optionBool('allow_show_all');
			$ellip = $this->option($is_html ? 'html_ellipsis' : 'text_ellipsis', '...');
			if ($allow_show_all) {
				$this->response()->html()->javascript('/share/zesk/js/zesk.js', [
					'weight' => 'first',
				]);

				$my_id = substr(md5(microtime()), 0, 8);
				$ellip = "<a onclick=\"ellipsis_toggle('$my_id')\">$ellip</a>";
				$close_ellip = "<a onclick=\"ellipsis_toggle('$my_id')\">&lt;&lt;</a>";
				$old_v = $v;
			}
			if ($is_html) {
				$v = HTML::ellipsis($v, $showSize, $ellip);
			} else {
				$v = HTML::ellipsis($v, $showSize, $ellip); // TODO?
			}
			if ($allow_show_all && $old_v !== $v) {
				$v = "<span id=\"ellipsis-$my_id\">$v</span><span style=\"display: none\" id=\"ellipsis-$my_id-all\">$old_v $close_ellip</span>";
			}
		}
		if ($this->hasOption('format')) {
			$v = $this->object->applyMap($this->option('format'));
		}
		if ($this->optionBool('debug')) {
			dump($this->object);
			echo "$v<br />";
		}
		return $this->render_finish($v);
	}
}
