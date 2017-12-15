<?php
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
	protected $javascript_settings_inherit = array(
		'show_count' => 3
	);
	
	/**
	 * Associated classes
	 * 
	 * @return array
	 */
	protected $model_classes = array(
		'zesk\\Help',
		'zesk\\Help_User'
	);
	
	/**
	 * We inject our JavaScript on pages; it loads
	 * 
	 * @param Request $requset        	
	 * @param Response_Text_HTML $response        	
	 */
	public function hook_head(Request $request, Response_Text_HTML $response, Template $template) {
		if (!$this->option_bool("disabled")) {
			$response->javascript('/share/help/js/help.js', array(
				'share' => true
			));
			$this->javascript_settings['active'] = false;
			try {
				$user = $this->application->user(false);
				$this->authenticated = $user ? $user->authenticated() : false;
				if ($this->authenticated) {
					$this->javascript_settings['active'] = true;
				}
			} catch (Database_Exception $e) {
				$this->application->logger->debug("{class}::hook_head threw exception {e}", array(
					'class' => __CLASS__,
					'e' => $e
				));
			}
		}
		parent::hook_head($request, $response, $template);
	}
	
	/**
	 * Registers all help text in the system
	 */
	public function hook_cron_cluster() {
		$application = $this->application;
		$helps = $application->modules->all_hook_arguments("module_help", array(), array());
		$this->application->logger->notice("{class}::cron found {count} help items", array(
			"count" => count($helps),
			'class' => __CLASS__
		));
		foreach ($helps as $target => $settings) {
			if ($settings === null) {
				$item = $application->orm_factory('zesk\\Help')->find(array(
					'target' => $target
				));
				if ($item) {
					$this->application->logger->notice("Deleted help item for {target}", array(
						'target' => $target
					));
					$item->delete();
				}
				continue;
			}
			$locale = Locale::current();
			foreach (to_list("title", "content") as $translation_key) {
				if (array_key_exists($translation_key, $settings)) {
					// Will register with localization. Is there a more explicit way to do this?
					Locale::translate($settings[$translation_key], $locale);
				}
			}
			$settings['target'] = $target;
			$help = $application->orm_factory('zesk\\Help', $settings)->register();
			/* @var $help Help */
			if ($help->object_status() === ORM::object_status_insert) {
				$this->application->logger->notice("Registered help item for {target}", array(
					'target' => $target
				));
			} else if ($this->option_bool('cron_update')) {
				$help->set_member($settings)->store();
			}
		}
	}
	
	/**
	 * Adds needed routes to our router
	 * 
	 * @param Router $router        	
	 */
	public function hook_routes(Router $router) {
		$router->add_route('help/user-targets', array(
			'method' => array(
				$this,
				"user_targets"
			),
			'arguments' => array(
				'{request}',
				'{response}'
			)
		));
		$router->add_route('help/user-reset', array(
			'method' => array(
				$this,
				"user_reset"
			),
			'arguments' => array(
				'{request}',
				'{response}'
			)
		));
		$router->add_route('help/show', array(
			'method' => array(
				$this,
				"user_show"
			),
			'arguments' => array(
				'{request}',
				'{response}'
			)
		));
		$router->add_route('help/dismiss', array(
			'method' => array(
				$this,
				"user_dismiss"
			),
			'arguments' => array(
				'{request}',
				'{response}'
			)
		));
	}
	
	/**
	 * List user targets
	 *
	 * @param Request $request        	
	 * @param Response $response        	
	 */
	public function user_targets(Request $request, Response $response) {
		$application = $this->application;
		$user = $this->_help_auth($request, $response);
		$query = $this->application->orm_registry('zesk\\Help')
			->query_select()
			->link('zesk\\Help_User', array(
			'require' => false,
			"alias" => "hu",
			"on" => array(
				'user' => $user
			)
		))
			->where(array(
			"X.active" => true,
			"hu.user" => null
		));
		$helps = $query->object_iterator();
		$result = array();
		$mappables = $application->modules->all_hook_arguments("module_help_map", array(), array());
		if (count($mappables) === 0) {
			$mappables = array();
		}
		foreach ($helps as $id => $help) {
			/* @var $help Help */
			$additional_map = $help->map;
			if (!is_array($additional_map)) {
				$additional_map = array();
			}
			$help_map = $additional_map + $mappables;
			
			$help_entry = map($help->members('target;placement'), $help_map);
			
			$help_entry['title'] = __($help->title, $help_map);
			
			$content_wraps = $help->content_wraps;
			$content = __($help->content, $help_map);
			if (is_array($content_wraps)) {
				$content = _W($content, $content_wraps);
			}
			
			$help_entry['content'] = $content;
			
			$result[$id] = $help_entry;
		}
		if ($application->development()) {
			$result[] = strval($query);
		}
		$response->json($result);
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
		$user = $this->application->user(false);
		if (!$user || !$user->authenticated()) {
			$response->status(Net_HTTP::Status_Unauthorized, "Requires user")->json(array(
				'error' => __('Requires user')
			));
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
			$response->status(Net_HTTP::Status_Method_Not_Allowed, "Requires POST")->json(array(
				'error' => __('Requires POST data')
			));
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
	public function user_show(Request $request, Response $response) {
		if (($user = $this->_help_update($request, $response)) === null) {
			return;
		}
		$application = $this->application;
		$ids = $request->geta('id', array());
		foreach ($ids as $id) {
			$application->orm_factory('zesk\\Help', $id)->show();
		}
		$response->json(array(
			'status' => true,
			'message' => 'Marked'
		));
	}
	
	/**
	 *
	 * @param Request $request        	
	 * @param Response $response        	
	 */
	public function user_dismiss(Request $request, Response $response) {
		if (($user = $this->_help_update($request, $response)) === null) {
			return;
		}
		$application = $this->application;
		$ids = $request->geta('id', array());
		foreach ($ids as $id) {
			$help = $application->orm_factory('zesk\\Help', $id)->fetch();
			if ($help) {
				$application->orm_factory('zesk\\Help_User', array(
					'help' => $help,
					'user' => $user,
					'dismissed' => 'now'
				))->register();
				$result[$id] = true;
			} else {
				$this->application->logger->error("{class}::{method} Help {id} not found", array(
					"class" => __CLASS__,
					"method" => __METHOD__,
					"id" => $id
				));
				$result[$id] = false;
			}
		}
		$response->json($result);
	}
	
	/**
	 * Remote call to reset all help for a user
	 *
	 * @param Request $request        	
	 * @param Response $response        	
	 */
	public function user_reset(Request $request, Response $response) {
		if (($user = $this->_help_auth($request, $response)) === null) {
			return;
		}
		$n = $this->reset_user($user);
		$response->json(array(
			'status' => true,
			'message' => __('Removed {n} {entries}', array(
				'n' => $n,
				'entries' => Locale::plural(__('entry'), $n)
			))
		));
	}
	
	/**
	 * Internal function to reset a user's help
	 *
	 * @param User $user        	
	 * @return number Number of affected records (number previous marked as "dismissed")
	 */
	public function reset_user(User $user) {
		$query = $this->application->class_query_delete('Help_User')->where('user', $user);
		$query->execute();
		return $query->affected_rows();
	}
}
