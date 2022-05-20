<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage default
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2017, Market Acumen, Inc.
 */

namespace zesk;

/**
 *
 * @author kent
 *
 */
class Control_IP_List extends Control {
	public const OPTION_ALLOW_MASKS_bool = 'allow_ip_masks';

	/**
	 *
	 * @var array
	 */
	private $ErrorIPs = [];

	/**
	 *
	 * @param boolean $set
	 * @return self|boolean
	 */
	public function allow_ip_masks($set = null) {
		return $set === null ? $this->optionBool(self::OPTION_ALLOW_MASKS_bool) : $this->setOption(self::OPTION_ALLOW_MASKS_bool);
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Widget::load()
	 */
	public function load(): void {
		$name = $this->name();
		$value = trim($this->request->get($name, '') . ' ' . $this->request->get($name . '_errors', ''));
		$value = preg_replace('#[^.*0-9/ ]#', '', $value);
		$this->value($value);
		parent::load();
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Widget::validate()
	 */
	public function validate(): bool {
		$allow_ip_masks = $this->optionBool('allow_ip_masks');
		$col = $this->column();
		$name = $this->name();
		$value = $this->value();
		$iplist = ArrayTools::trim(explode(' ', preg_replace('/[\s, ]+/', ' ', $value)));
		//		sort($iplist, SORT_NUMERIC);
		$check_func = $allow_ip_masks ? [IPV4::class, 'is_mask'] : [IPV4::class, 'valid'];
		foreach ($iplist as $k => $ip) {
			if (empty($ip)) {
				unset($iplist[$k]);

				continue;
			}
			if (!call_user_func($check_func, $ip)) {
				$this->ErrorIPs[] = $ip;
				unset($iplist[$k]);
			}
		}
		$this->value(implode("\n", $iplist));
		if (count($this->ErrorIPs) > 0) {
			$this->error('Some IP addresses were incorrectly formatted, please check your work below.', $col);
			return false;
		}
		return true;
	}

	/**
	 * @todo Move to template
	 * {@inheritDoc}
	 * @see \zesk\Widget::render()
	 */
	public function render(): string {
		$col = $this->column();
		$name = $this->name();
		$errors = '';
		$attrs = $this->options([
			'rows' => 10,
			'cols' => 20,
			'id' => $col . '_ip_list',
			'name' => $name,
		]);
		$ip = $this->request->ip();
		$response = $this->response();
		$response->html()->javascript('/share/zesk/widgets/iplist/iplist.js');
		$response->html()->css('/share/zesk/widgets/iplist/iplist.css');

		$add_ip = "<a class=\"ip-list-add\" id=\"${name}\" onclick=\"iplist_add('${col}_ip_list', '${ip}')\">" . __('Add current IP') . ": $ip</a>";
		if (count($this->ErrorIPs)) {
			$err_attrs = $attrs;
			$err_attrs['name'] = $name . '_errors';
			$err_attrs['id'] = $col . '_ip_list_errors';
			$errors = HTML::tag('div', [
				'class' => 'ip-list ip-list-errors',
			], HTML::tag('div', [
					'class' => 'ip-list-textarea',
				], HTML::tag('textarea', $err_attrs, implode("\n", $this->ErrorIPs))) . HTML::etag('label', '', $this->option('error_ip_list_label', 'Errors')));
		}
		$result = HTML::tag('div', [
				'class' => 'ip-list',
			], HTML::tag('div', [
					'class' => 'ip-list-textarea',
				], HTML::tag('textarea', $attrs, $this->value()) . $add_ip) . HTML::etag('label', '', $this->option('ip_list_label', ''))) . $errors;
		$result = HTML::tag('div', [
			'class' => 'ip-list-widget',
		], $result);
		return $result;
	}
}
