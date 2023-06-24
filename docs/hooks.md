# Hooks in Zesk

## What are hooks?

A hook is the ability to run code at a specific point in time during a program. Hooks in Zesk allow you to:

- Modify default objects in the system and base class behaviors
- Enhance functionality to existing programs automatically

Hooks in Zesk in PHP 8 are added to methods using PHP `Attribute`s which allow for extension of the language and
identification of your hooks in your code.

Alternatively, you can register a `Closure` or `callable` for a hook which will be invoked in certain circumstances.

[List of standard Zesk hooks](hooks-list.md)

## Change from prior system - no autoloader

The previous system depended on code being loaded by Zesk and then used the class and method namespace naming to figure
out which methods were hooks. This has the drawback that you have to name your method appropriately, and in some cases
the method names become cumbersomely long to handle various cases.

The 2023 hook system uses PHP method attributes, and loads all PHP in the application source code and any modules prior
to looking for hooks. One important distinction is that modules which are not loaded are not scanned until loaded.

## Adding a hook

You can annotate any method to add a hook:

    public class Foo {
        #[HookMethod(handles: Hooks::HOOK_CONFIGURED)]
        public static function configured(Application $application): void {
            ...
        }
    }

Or you can manually add them to the hooks object in the application:

    $app->hooks->registerHook(Hooks::HOOK_CONFIGURED, function (Application $application) {
        ...
    });

## Hook naming

Hooks names are case-sensitive and are not modified or cleaned at all - they must match exactly. To preserve hook name
space it is highly recommended to use your class names for hooks, such as:

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

## Parameters

TODO
