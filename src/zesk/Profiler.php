<?php
declare(strict_types=1);

namespace zesk;

use zesk\Application\Hooks;

class Profiler {
	/**
	 * @var array
	 */
	public array $calls = [];

	/**
	 * @var array
	 */
	public array $times = [];

	/**
	 *
	 */
	public function __construct(Hooks $hooks) {
		$hooks->add('</body>', function (): void {
			echo $this->render();
		});
	}

	public function render(): string {
		$content = '<pre>';
		asort($this->calls);
		asort($this->times);
		$content .= print_r($this->calls, true);
		$content .= print_r($this->times, true);
		$content .= '</pre>';
		return $content;
	}
}
