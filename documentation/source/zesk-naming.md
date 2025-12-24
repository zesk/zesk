# Zesk Naming Conventions

This article outlines how developers should name features of their code related to Zesk. This is a requirement for Zesk [core](), or Zesk [modules](), and optional for  applications built on Zesk. It covers:

- File names
- Directory names
- File Extensions
- Class names
- Class method names
- Hook names
- Configuration settings names
- Option names

# Background Material

- [Naming Styles Definitions][]
- [PHP Case Sensitivity][]

## Files

PHP file naming MUST follow PSR-4. Non-code files should mimic existing file systems and naming structures.

## File Extensions

Zesk supports the following types of file extensions, and conventions:

### PHP Files end with `.php`, or `.tpl`

- `.php` PHP Code
- `.tpl` Template files and are invoked via the PHP interpreter, usually as an `include`

PHP files of all types should have `<``?php` as the first line in the file, and the trailing `?>` should be left off to avoid trailing white space issues.

### Configuration files end with `.conf`

The configuration file format *may* be compatible with Unix `sh` or `bash` interpreters and can be used to share configuration settings across interpreters.

The basic configuration format is:

    STYLE_PATH=${ZESK_SITE_ROOT}/designs
    APP_STYLE=orange
	MAIN_CSS=$STYLE_PATH/$APP_STYLE/file.css

More about configuration files in [configuration files][].

### Router files end with `.router`

A special file type called a "router" file is the file format for the `Router` class to easily set up web site routes.

The format is similar to [configuration files][] however it uses a space-based indentation scheme, as follows:

    pattern
        template=login.tpl
        login=true
        page template=page/login.tpl
	login
	logout
	    controller=login
	    action="{0}"

More about router files in [router files][].

## Classes

Zesk core classes are loaded via PSR-4 and composer.

Modules may be added to Zesk's internal autoloader using the [Modules](./modules.md) functionality, or by adding paths
manually via `$application->addAutoloadPath()` during application configuration.

## Hooks

Hooks are a simple but powerful method your code to interact with various behaviors in the system. As a general rule, Hooks are named as follows:

- TODO

### Hookable syntax

Classes which inherit from `zesk\Hookable` have hook functionality built in. To invoke a hook:

    $x->callHook('notify', $emails);

### Class syntax

The `Hookable` class invokes `hook_`*`message`* first, then calls the class hierarchy version of a hook. By way of example, given the following class:

	class MenuItem extends \zesk\Doctrine\Model {
		...
	}
	class FoodItem extends MenuItem {
		...
	}
	class Pizza extends FoodItem {
		function hook_delivered(Location $location) {
			...
		}
		function check_delivered() {
			$location = $this->getLocation();
			$truck_location = $this->deivery_truck()->location();
			if ($location->within_radius(100 * Location::METERS)) {
				$this->arrived = Timestamp::now();
				$this->store();
				$this->callHook("delivered", $location);
			}
		}
	}

When we call `$pizza->check_delivered()`, and our hook is called, the following happens:

- `$this->hook_delivered(...)` is called first with the parameters
- `$this->options["hooks"]["delivered"]` is called with the value of `$this` (our `Pizza` instance) passed as the first parameter, followed by the other parameters passed

The following global `$application->hooks` are called, in order with the value of `$this` passed as the first parameter, followed by the other parameters passed:

 - `Pizza::delivered`
 - `FoodItem::delivered`
 - `MenuItem::delivered`
 - `zesk\Doctrine\Model::delivered`
 - `zesk\Model::delivered`
 - `zesk\Hookable::delivered`

So, if we wanted to intercept this via a hook, we could do this in our application configuration:
	
	$this->hooks->add("Pizza::delivered", function (Pizza $pizza, Location $location) {
		$sms = $pizza->order->sms_notify;
		if ($sms) {
			$order_id = $pizza->order->code;
			$pizza->application->smsModule()->submitMessageTo($sms, "Your pizza order #$order_id was delivered");
		}
	});

Results from the hook are combined using `zesk\Hookable::hook_results`, which, in the simplest case:

- concatenates strings, or
- merges arrays, or
- returns the last hook result

TODO more explanation and examples of how to use this, also how to run filters on objects, and how to use the hook callbacks.

[configuration files]: configuration-file-format.md "Configuration File Format"
[router files]: router-file-format.md "Router File Format"
[Naming Styles Definitions]: naming-styles-definitions.md "Naming Styles Definitions"
[PHP Case Sensitivity]: php-case-sensitivity.md "PHP Case Sensitivity"
[PSR-4]: http://www.php-fig.org/psr/psr-4/ "PHP Autoloading Standard"
