<?php
declare(strict_types=1);

/**
 * @package zesk
 * @subpackage widgets
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 * Created on Tue Jul 15 16:38:32 EDT 2008
 */

namespace zesk;

class Control_Image_Toggle extends Control {
	public function render(): string {
		$locale = $this->locale();
		$value = $this->value();
		$on_value = $this->option('true_value', true);

		$attrs = $this->options(to_list('width;height;border;hspace;vspace'));
		$response = $this->response();
		$id = 'toggle_image_' . $response->id_counter();
		$js_object = $this->options([
			'true_src' => null,
			'true_alt' => $locale->__('Click here to enable'),
			'false_src' => null,
			'false_alt' => $locale->__('Click here to disable'),
			'notify_url' => null,
			'true_value' => 'true',
			'false_value' => 'false',
		]);
		$prefix = ($value === $on_value) ? 'true' : 'false';

		$div_attrs = $this->options([
			'class' => 'ControlToggleImage',
			'style' => null,
		]);
		$div_attrs['id'] = $id;
		$src = $js_object[$prefix . '_src'] ?? null;
		if (!$src) {
			return '';
		}
		$content = HTML::tag('div', $div_attrs, HTML::img($this->application, $src, $js_object[$prefix . '_alt'] ?? '', $attrs));
		$response->html()->jquery('$(\'#' . $id . '\').toggleImage(' . json_encode($js_object) . ');');

		return $content;
	}
}
