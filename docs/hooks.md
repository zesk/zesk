# Hooks in Zesk

## What are hooks?

A hook is the ability to run code at a specific point in time during a program. Hooks in Zesk allow you to:

- Modify objects in the system from external modules or your application
- Enhance functionality to existing programs with no modifications

Hooks in Zesk in PHP 8 are added to methods using PHP `Attribute`s which allow for extension of the language and
identification of your hooks in your code.

Alternatively, you can register a `Closure` or `callable` for a hook which will be invoked in certain circumstances.

[List of standard Zesk hooks](hooks-list.md)

## No autoloader

The previous system depended on the code being loaded by Zesk. The current system scans all of the code and uses PHP's
introspection to determine where hooks reside in the code.

## Registering your hooks

You can annotate any method to add a hook:

    public class Foo {
        #[HookMethod(handles: Hooks::HOOK_CONFIGURED)]
        public static function configured(Application $application): void {
            ...
        }
    }

Or you can manually add them to the hooks object in the application:

    $this->application->hooks->registerHook(Hooks::HOOK_CONFIGURED, function (Application $application) {
        ...
    });

## Hook naming

Hooks are case-sensitive and are not processed at all - they must match exactly. It is highly recommended to use your
class names for hooks, such as:

    public class Foo {
        public const HOOK_BAR = __CLASS__ . '::bar';

        #[HookMethod(handles: self::HOOK_BAR)]
        public function walkIntoIt(): void {
            ...
        }
    }

## Call a hook

Hooks exist and may be called in different forms:

- Static methods which do not require an object
- Object methods which require an object to be invoked

The differences are shown here:

    public class Foo {
        public const HOOK_BAR = __CLASS__ . '::bar';

        #[HookMethod(handles: self::HOOK_BAR)]
        public function walkIntoIt(): void {
            ...
        }
        #[HookMethod(handles: Hooks::HOOK_CONFIGURED)]
        public static function configured(Application $application): void {
            ...
        }
    }

Any object which inherits "Hookable" can be used to invoke a hook:

    /* @var $u User */
    $u = User::instance();
    try {
        $u->invokeHook(User::HOOK_ALLOW_REQUEST, [$request]);
	} catch (Throwable) {
        // Request not allowed
    }

Hooks have a special form called a `filter` which means it takes a `mixed` value which is passed to each function and,
it is assumed, returned by each method.

You have the option of passing the argument as any parameter in the arguments list, however, note that all filters in
your system must follow a compatible method signature in order to operate correctly.

    $jsonArray = $application->invokeFilter(Response::HOOK_JSON_POSTPROCESS, $jsonArray, [$request]);

Similarly, if you need each filter to return the same type as passed in each method call or fail, then use:

    $jsonArray = $application->invokeTypedFilter(Response::HOOK_JSON_POSTPROCESS, $jsonArray, [$request]);

Note that it enforces that `zesk\Types::type($mixed)` does not change at any point during the filter hook chain.

Hooks are usually called as a sequence, and in general, when a hook is run, it first runs:

- Static hook methods
- Application object hook methods
- Any custom objects requested

You can isolate which hooks are run to a specific object, and, obviously, which hook is run by name.

The invocation methods are:

- `zesk\Hookable::invokeHook`, `zesk\Hookable::invokeFilter` - Run hooks across all static hooks

## Hookable call order

Conceptually, a hook attached to an object implements `hook_foo` and then whenever the `foo` hook is called, the
function gets invoked.

	class Project extends Hookable {
		function hook_finish() {
			$this->clean_up_files();
			$this->put_away_resources();
			$this->zip_up_files();
			$this->move_to_archive_folder();
		}
		...
	}

Now, Captain Obvious would point out that "You can use regular PHP inheritance for that!" And Captain Obvious would be
correct. However, the hook system allows for something which regular PHP inheritance does not allow:

- programmatic interception of hooks on a per-object basis
- external access to internal hooks

Let's handle these each in order:

### Interception of hooks on a per-object basis

You'll note that [`zesk\Hookable`](`hookable.md`) inherits from [`zesk\Options`](options.md) so it inherits the ability
to set arbitrary options on any `Hookable` object. There's a special option called "hooks" which allows the user to
define a hook to be called. You can then turn this on and off for a specific object if you wish:

	DEPRECATED TODO $this->option_append("hooks", "delete", "project_deleted");

Then the method `project_deleted` will be called with our object upon deletion.

## External access to internal hooks

Note as well that we can also TODO

# Registration based vs. function and method name space

How do you get your hook called, you say? In one of three ways:

- Call `zesk()->hooks->add($hook, $function)` to register your hook in the global hook tables
- Create a method called "`hook_$hook`" inside of a hookable class or subclass.
- Register your class with `zesk()->classes->register()` and then invoke using `zesk()->hooks->allCall($method)`

`zesk()->hooks->call` and `zesk()->hooks->call_arguments` are essentially just buckets where you can register your hook.
Zesk global hooks are registration-based.

Object-hooks are method space declaration. Meaning if you create a method in your object which is named

	class Foo extends Hookable {
		function hook_dee() {
			echo "Dee called";
		}
	}
	$foo = new Foo();
	$foo->callHook("dee");

Then your method will be called at the appropriate time.

The final mechanism allows for hooks to be registered as static class methods, and just by registering your class will
you be able to be called at the appropriate place.

	class Foo extends zesk\Module {
 		static function add_to_cart() {
			echo "added to cart";
		}
	}
	
	$product = ORM::factory('Product')->fetch(23);
	zesk()->hooks->call_all("Module::add_to_cart", $product);

Calls method "add_to_cart" in all classes of type "Module" (if the method exists)

# Configuring and manipulating arguments and return values

Typically, calling a hook requires a list of arguments to be passed to each hook, and then each hook may optionally
return a value.

Things to consider is that a single hook may be a single function, or multiple functions which are called in order.

The question is: _How do we handle these cases for single, multiple hooks, and return values?_

The hooks system is designed to handle common cases for this.

## Return values

TODO
