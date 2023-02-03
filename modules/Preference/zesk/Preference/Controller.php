<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk\Preference;

use Throwable;
use zesk\ArrayTools;
use zesk\Controller as BaseController;
use zesk\Exception_File_NotFound;
use zesk\Exception_File_Permission;
use zesk\Exception_Parameter;
use zesk\File;
use zesk\Hooks;
use zesk\HTTP;
use zesk\PHP;
use zesk\Request;
use zesk\Response;

/**
 * Very similar to Controller_Setting - refactor both
 *
 * @author kent
 */
class Controller extends BaseController {
	protected array $argumentMethods = [
		'arguments_{METHOD}', 'arguments',
	];

	protected array $actionMethods = [
		'action_{METHOD}',
	];

	protected array $beforeMethods = [];

	protected array $afterMethods = [];

	/**
	 *
	 * @var array
	 */
	protected array $whitelist = [];

	/**
	 *
	 * @return string
	 */
	public function _whitelistFile(): string {
		$file = $this->optionString('whitelistFile', './etc/preferences.txt');
		return $this->application->paths->expand($file);
	}

	/**
	 *
	 */
	public function save_preferences(): void {
		file_put_contents($this->_whitelistFile(), implode("\n", array_keys($this->whitelist)));
	}

	/**
	 * @return array
	 * @throws Exception_File_Permission
	 */
	private function _whitelist(): array {
		if (count($this->whitelist) === 0) {
			try {
				$this->whitelist = array_flip(ArrayTools::clean(explode("\n", File::contents($this->_whitelistFile(), '')), ''));
			} catch (Exception_File_NotFound $e) {
				$this->whitelist = [];
			}
			if ($this->optionBool('autoRegister')) {
				$this->application->hooks->add(Hooks::HOOK_EXIT, $this->save_preferences(...), ['first' => true]);
			}
		}
		return $this->whitelist;
	}

	/**
	 * Add a key to the whitelist to permit saving later.
	 *
	 * @param string $name
	 * @return void
	 * @throws Exception_File_Permission
	 */
	private function _addToWhitelist(string $name): void {
		$this->_whitelist();
		$this->whitelist[$name] = true;
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @param array $arguments
	 * @return array
	 * @throws Exception_Parameter
	 * @throws Exception_File_Permission
	 */
	public function arguments(Request $request, Response $response, array $arguments): array {
		if (count($arguments) === 0) {
			throw new Exception_Parameter('Need a name');
		}
		$name = $arguments[0];
		$whitelist = $this->_whitelist();
		if (!array_key_exists($name, $whitelist)) {
			if ($this->optionBool('autoRegister')) {
				$this->_addToWhitelist($name);
			}
		}
		return [
			$request, $response, $name,
		];
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return array
	 */
	public function argument_OPTIONS(Request $request, Response $response): array {
		return [$response, $request];
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @param string $name
	 * @return Response
	 * @dataProvider argument_OPTIONS
	 */
	public function action_OPTIONS(Response $response): Response {
		return $this->handleOPTIONS($response, '');
	}

	/**
	 *
	 * @param Request $request
	 * @param Response $response
	 * @param string $name
	 * @return Response
	 * @dataProvider arguments
	 */
	public function action_GET(Request $request, Response $response, string $name): Response {
		$user = $this->application->requireUser($request);
		return $response->json()->setData(['name' => $name, 'value' => Value::userGet($user, $name)]);
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @param string $name
	 * @return Response
	 * @dataProvider arguments
	 */
	public function action_POST(Request $request, Response $response, string $name): Response {
		$user = $this->application->requireUser($request);
		$value = PHP::autotype($request->get('value'));

		try {
			Value::userSet($user, [$name => $value]);
			return $response->json()->setData(['name' => $name, 'value' => $value]);
		} catch (Throwable $t) {
			$response->setStatus(HTTP::STATUS_CONFLICT);
			return $response->json()->setData(['name' => $name]);
		}
	}
}
