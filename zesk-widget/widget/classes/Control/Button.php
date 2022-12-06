<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 * Created on Tue Jul 15 16:38:57 EDT 2008
 */

namespace zesk;

class Control_Button extends Control {
	/**
	 * Setting this converts the button into an A tag
	 * @return string
	 */
	public function href(): string {
		return $this->option('href', '');
	}

	/**
	 * Setting this converts the button into an A tag
	 * @param string $set
	 * @return Control_Button|string
	 */
	public function setHref(string $set): self {
		return $this->setOption('href', $set);
	}

	public function buttonLabel(): string {
		return $this->option('button_label', '');
	}

	/**
	 * @param string $set
	 * @return self
	 */
	public function setButtonLabel(string $set): self {
		return $this->setOption('button_label', $set);
	}

	public function submit(): bool {
		if (($url = $this->option('redirect_url')) !== null) {
			$url = $this->object->applyMap($url);
			$url = URL::queryFormat($url, [
				'ref' => $this->request->uri(),
			]);

			throw new Exception_Redirect($url, $this->object->applyMap($this->option('redirect_message')));
		}
		return true;
	}

	public function themeVariables(): array {
		return parent::themeVariables() + [
			'href' => $this->href(),
			'button_label' => $this->buttonLabel(),
		];
	}
}
