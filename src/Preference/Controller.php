<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk\Preference;

use Throwable;
use zesk\Application\Hooks;
use zesk\ArrayTools;
use zesk\Controller as BaseController;
use zesk\Exception\ClassNotFound;
use zesk\Exception\FileNotFound;
use zesk\Exception\FilePermission;
use zesk\Exception\KeyNotFound;
use zesk\Exception\NotFoundException;
use zesk\Exception\ParameterException;
use zesk\Exception\ParseException;
use zesk\Exception\SemanticsException;
use zesk\File;
use zesk\HTTP;
use zesk\Request;
use zesk\Response;
use zesk\Types;

/**
 * Very similar to Controller_Setting - refactor both
 *
 * @author kent
 */
class Controller extends BaseController {
	
	protected array $argumentMethods = [
		'arguments_{METHOD}',
		'arguments',
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
	protected array $allowed = [];
	
	/**
	 *
	 * @return string
	 */
	public function _whitelistFile(): string
	{
		$file = $this->optionString('whitelistFile', './etc/preferences.txt');
		return $this->application->paths->expand($file);
	}
	
	/**
	 *
	 */
	public function save_preferences(): void
	{
		file_put_contents($this->_whitelistFile(), implode("\n", array_keys($this->allowed)));
	}
	
	/**
	 * @return array
	 * @throws FilePermission
	 */
	private function _allowedList(): array
	{
		if (count($this->allowed) === 0) {
			try {
				$this->allowed = array_flip(ArrayTools::clean(explode("\n", File::contents($this->_whitelistFile())), ''));
			} catch (FileNotFound $e) {
				$this->allowed = [];
			}
			if ($this->optionBool('autoRegister')) {
				$this->application->hooks->registerHook(Hooks::HOOK_EXIT, $this->save_preferences(...));
			}
		}
		return $this->allowed;
	}
	
	/**
	 * Add a key to the allowed list to permit saving later.
	 *
	 * @param string $name
	 * @return void
	 * @throws FilePermission
	 */
	private function _addToAllowedList(string $name): void
	{
		$this->_allowedList();
		$this->allowed[$name] = true;
	}
	
	/**
	 * @param Request  $request
	 * @param Response $response
	 * @param array    $arguments
	 * @return array
	 * @throws ParameterException
	 * @throws FilePermission
	 */
	public function arguments(Request $request, Response $response, array $arguments): array
	{
		if (count($arguments) === 0) {
			throw new ParameterException('Need a name');
		}
		$name = $arguments[0];
		$allowedList = $this->_allowedList();
		if (!array_key_exists($name, $allowedList)) {
			if ($this->optionBool('autoRegister')) {
				$this->_addToAllowedList($name);
			}
		}
		return [
			$request,
			$response,
			$name,
		];
	}
	
	/**
	 * @param Request  $request
	 * @param Response $response
	 * @return array
	 */
	public function argument_OPTIONS(Request $request, Response $response): array
	{
		return [
			$response,
			$request,
		];
	}
	
	/**
	 * @param Response $response
	 * @return Response
	 * @dataProvider argument_OPTIONS
	 */
	public function action_OPTIONS(Response $response): Response
	{
		return $this->handleOPTIONS($response, '');
	}
	
	/**
	 *
	 * @param Request  $request
	 * @param Response $response
	 * @param string   $name
	 * @return Response
	 * @throws NotFoundException
	 * @throws ClassNotFound
	 * @throws KeyNotFound
	 * @throws SemanticsException
	 * @dataProvider arguments
	 */
	public function action_GET(Request $request, Response $response, string $name): Response
	{
		$user = $this->application->requireUser($request);
		return $response->json()->setData([
			'name'  => $name,
			'value' => Value::userGet($user, $name),
		]);
	}
	
	/**
	 * @param Request  $request
	 * @param Response $response
	 * @param string   $name
	 * @return Response
	 * @throws ClassNotFound
	 * @throws KeyNotFound
	 * @throws ParseException
	 * @throws SemanticsException
	 * @dataProvider arguments
	 */
	public function action_POST(Request $request, Response $response, string $name): Response
	{
		$user = $this->application->requireUser($request);
		$value = Types::autoType($request->get('value'));
		
		try {
			Value::userSet($user, [$name => $value]);
			return $response->json()->setData([
				'name'  => $name,
				'value' => $value,
			]);
		} catch (Throwable $t) {
			$response->setStatus(HTTP::STATUS_CONFLICT);
			return $response->json()->setData(['name' => $name]);
		}
	}
}
