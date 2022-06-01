# Zesk Debugging 

When writing and maintaining code it's important to be able to flag sections of code where more information is needed to track down a specific issue. Ideally this would be a run-time flag which allows us to turn the debugging messages on or off depending on the server or application context. This document outlines a pattern used in Zesk and recommended in your own code for enabling flags

## Functionality Flags

The general practice for setting debugging flags in your classes is to use a configuration option which is loaded into the global `zesk\Application` `zesk\Configuration` object.

Naming should be as follows:

- `namespace\ClassName::debug` - Enable debugging for entire class
- `namespace\ClassName::debug_foo` - Enable debugging for class-specific feature `foo`

This is a RECOMMENDATION and variations among classes may be permitted depending on your configuration needs.

### Implementation via static debugging state:

	use zesk\Application;
	use zesk\Hooks;
	
    class MailSender {
		public static $debug = false;
		
		public static function hooks(Application $zesk) {
			$zesk->hooks->add(Hooks::HOOK_CONFIGURED, array(
				__CLASS__,
				"configured"
			));
		}
		public static function configured(Application $app) {
			self::$debug = toBool($app->configuration->path_get(array(__CLASS__, "debug")));
		}
		
		// Snip
	}

### Implementation on a per-object basis:

	use zesk\Hookable;
	
    class MailSender extends Hookable {
		//
		// Condensed for this example
		//
		
		/**
		 * Initialize the MailSender
		 */
		public function initialize(): void {
			parent::initialize();
			$this->inheritConfiguration();
		}
		
		/**
		 * Log a message if debugging is enabled
		 *
		 * @option boolean debug 
		 * @param string $message Message to send, e.g. "Something is {verb}"
		 * @param array $args Message arguments, e.g. array("verb" => "awry")
		 * @return void
		 */
		public function debug($message, array $args = array()) {
			if ($this->optionBool("debug")) {
				$this->application->logger->debug($message, $args);
			}
		}
		/**
		 * Send the current message
		 *
		 * @option boolean debug 
		 * @option boolean debug_send
		 * @return self
		 */
		public function send() {
			if ($this->optionBool("debug_send", $this->optionBool("debug"))) {
				$this->application->logger->debug("Sending message {id}", array("id" => $this->id));
			}
		}
		// Snip
	}

TODO: @option - implement something like this?
