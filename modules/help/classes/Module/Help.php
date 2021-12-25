<?php declare(strict_types=1);
/**
 * Module to present context-sensitive help on web pages.
 *
 * Uses Help for help text storage and Help_User to mark entries
 *
 * Only works on authenticated users; perhaps add a session marker to allow per-session
 * help text for apps which is then copied into the user context.
 *
 * @author kent
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Module_Help extends Module_JSLib {
	/**
	 *
	 * @var unknown
	 */
	private $authenticated = null;

	/**
	 *
	 * @var array
	 */
	protected $javascript_settings_inherit = [
		'show_count' => 3,
	];

	/**
	 * Associated classes
	 *
	 * @return array
	 */
	protected array $model_classes = [
		'zesk\\Help',
		'zesk\\Help_User',
	];

	/**
	 * We inject our JavaScript on pages; it loads
	 *
	 * @param Request $requset
	 * @param Response $response
	 */
	public function hook_head(Request $request, Response $response, Template $template): void {
		if (!$this->option_bool("disabled")) {
			$response->javascript('/share/help/js/help.js', [
				'share' => true,
			]);
			$this->javascript_settings['active'] = false;

			try {
				$user = $this->application->user($request, false);
				$this->authenticated = $user ? $user->authenticated($request) : false;
				if ($this->authenticated) {
					$this->javascript_settings['active'] = true;
				}
			} catch (Database_Exception $e) {
				$this->application->logger->debug("{class}::hook_head threw exception {e}", [
					'class' => __CLASS__,
					'e' => $e,
				]);
			}
		}
		parent::hook_head($request, $response, $template);
	}

	/**
	 * Registers all help text in the system
	 */
	public function hook_cron_cluster(): void {
		$application = $this->application;
		$helps = $application->modules->all_hook_arguments("module_help", [], []);
		$this->application->logger->notice("{class}::cron found {count} help items", [
			"count" => count($helps),
			'class' => __CLASS__,
		]);
		foreach ($helps as $target => $settings) {
			if ($settings === null) {
				$item = $application->orm_factory('zesk\\Help')->find([
					'target' => $target,
				]);
				if ($item) {
					$this->application->logger->notice("Deleted help item for {target}", [
						'target' => $target,
					]);
					$item->delete();
				}

				continue;
			}
			$locale = $this->application->locale;
			foreach (to_list("title", "content") as $translation_key) {
				if (array_key_exists($translation_key, $settings)) {
					// Will register with localization. Is there a more explicit way to do this?
					$locale->__($settings[$translation_key]);
				}
			}
			$settings['target'] = $target;
			$help = $application->orm_factory(Help::class, $settings)->register();
			/* @var $help Help */
			if ($help->object_status() === ORM::object_status_insert) {
				$this->application->logger->notice("Registered help item for {target}", [
					'target' => $target,
				]);
			} elseif ($this->option_bool('cron_update')) {
				$help->set_member($settings)->store();
			}
		}
	}

	/**
	 * Adds needed routes to our router
	 *
	 * @param Router $router
	 */
	public function hook_routes(Router $router): void {
		$router->add_route('help/user-targets', [
			'method' => [
				$this,
				"user_targets",
			],
			'arguments' => [
				'{request}',
				'{response}',
			],
		]);
		$router->add_route('help/user-reset', [
			'method' => [
				$this,
				"user_reset",
			],
			'arguments' => [
				'{request}',
				'{response}',
			],
		]);
		$router->add_route('help/show', [
			'method' => [
				$this,
				"user_show",
			],
			'arguments' => [
				'{request}',
				'{response}',
			],
		]);
		$router->add_route('help/dismiss', [
			'method' => [
				$this,
				"user_dismiss",
			],
			'arguments' => [
				'{request}',
				'{response}',
			],
		]);
	}

	/**
	 * List user targets
	 *
	 * @param Request $request
	 * @param Response $response
	 */
	public function user_targets(Request $request, Response $response): void {
		$application = $this->application;
		$user = $this->_help_auth($request, $response);
		$query = $this->application->orm_registry('zesk\\Help')
			->query_select()
			->link('zesk\\Help_User', [
			'require' => false,
			"alias" => "hu",
			"on" => [
				'user' => $user,
			],
		])
			->where([
			"X.active" => true,
			"hu.user" => null,
		]);
		$helps = $query->orm_iterator();
		$result = [];
		$mappables = $application->modules->all_hook_arguments("module_help_map", [], []);
		if (count($mappables) === 0) {
			$mappables = [];
		}
		$__ = $this->application->locale;
		foreach ($helps as $id => $help) {
			/* @var $help Help */
			$additional_map = $help->map;
			if (!is_array($additional_map)) {
				$additional_map = [];
			}
			$help_map = $additional_map + $mappables;

			$help_entry = map($help->members('target;placement'), $help_map);

			$help_entry['title'] = $__($help->title, $help_map);

			$content_wraps = $help->content_wraps;
			$content = $__($help->content, $help_map);
			if (is_array($content_wraps)) {
				$content = HTML::wrap($content, $content_wraps);
			}

			$help_entry['content'] = $content;

			$result[$id] = $help_entry;
		}
		if ($application->development()) {
			$result[] = strval($query);
		}
		$response->json()->data($result);
	}

	/**
	 * Require user authentication
	 *
	 * @param Request $request
	 * @param Response $response
	 *
	 * @return User
	 */
	private function _help_auth(Request $request, Response $response) {
		$user = $this->application->user($request, false);
		if (!$user || !$user->authenticated($request)) {
			$response->status(Net_HTTP::STATUS_UNAUTHORIZED, "Requires user")->json([
				'error' => $this->application->locale->__('Requires user'),
			]);
			return null;
		}
		return $user;
	}

	/**
	 * Require user authentication, and require POST (e.g.
	 * this is help call which "updates" things)
	 *
	 * @param Request $request
	 * @param Response $response
	 * @return NULL|User
	 */
	private function _help_update(Request $request, Response $response) {
		$user = $this->_help_auth($request, $response);
		if (!$user) {
			return null;
		}
		if (!$this->application->development() && !$request->is_post()) {
			$response->status(Net_HTTP::STATUS_METHOD_NOT_ALLOWED, "Requires POST")->json([
				'error' => $this->application->locale->__('Requires POST data'),
			]);
			return null;
		}
		return $user;
	}

	/**
	 * Show help to a user and mark Help record
	 *
	 * @param Request $request
	 * @param Response $response
	 */
	public function user_show(Request $request, Response $response): void {
		if (($user = $this->_help_update($request, $response)) === null) {
			return;
		}
		$application = $this->application;
		$ids = $request->geta('id', []);
		foreach ($ids as $id) {
			$application->orm_factory('zesk\\Help', $id)->show();
		}
		$response->json()->data([
			'status' => true,
			'message' => 'Marked',
		]);
	}

	/**
	 *
	 * @param Request $request
	 * @param Response $response
	 */
	public function user_dismiss(Request $request, Response $response): void {
		if (($user = $this->_help_update($request, $response)) === null) {
			return;
		}
		$application = $this->application;
		$ids = $request->geta('id', []);
		foreach ($ids as $id) {
			$help = $application->orm_factory('zesk\\Help', $id)->fetch();
			if ($help) {
				$application->orm_factory('zesk\\Help_User', [
					'help' => $help,
					'user' => $user,
					'dismissed' => 'now',
				])->register();
				$result[$id] = true;
			} else {
				$this->application->logger->error("{class}::{method} Help {id} not found", [
					"class" => __CLASS__,
					"method" => __METHOD__,
					"id" => $id,
				]);
				$result[$id] = false;
			}
		}
		$response->json()->data($result);
	}

	/**
	 * Remote call to reset all help for a user
	 *
	 * @param Request $request
	 * @param Response $response
	 */
	public function user_reset(Request $request, Response $response): void {
		$locale = $this->application->locale;
		if (($user = $this->_help_auth($request, $response)) === null) {
			$response->json()->data([
				'status' => false,
				'message' => $locale->__('You are not logged in'),
			]);
			return;
		}
		$n = $this->reset_user($user);
		$response->json()->data([
			'status' => true,
			'message' => $locale->__('Removed {n} {entries}', [
				'n' => $n,
				'entries' => $locale->plural($locale->__('entry'), $n),
			]),
		]);
	}

	/**
	 * Internal function to reset a user's help
	 *
	 * @param User $user
	 * @return number Number of affected records (number previous marked as "dismissed")
	 */
	public function reset_user(User $user) {
		return $this->application->orm_registry(Help_User::class)
			->query_delete()
			->where('user', $user)
			->execute()
			->affected_rows();
	}
}
