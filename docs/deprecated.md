# Deprecating functions

Part of library development requires us to retire old patterns and make room for new, improved ones. Removing code from a library is done using a process called ***deprecating*** and it's important that you understand how code is retired and set out to pasture.

## Deprecation policy: 6 month time-to-live

Our deprecation policy is that deprecated functions will continue to work, as advertised, for 6 months from date of deprecation. After that point they may be removed from the codebase.

All deprecated, removed code will be in an incremental code version (e.g. 1.2 to 1.3) so existing applications can rely on older libraries without fear of losing functionality between versions.

For pre-1.0 versions, we may remove calls between minor builds (0.8.1 and 0.8.2, for example).

## How functions are deprecated

Functions in zesk will be deprecated in two ways:

- The addition of the `@deprecated` DocComment flag in the PHP code
- The inclusion of a call to `zesk()->deprecated()` with an optional message outlining the reason
- When possible the addition of a `@see` DocComment to point to the new call to use in lieu of the current call

The `@deprecated` DocComment will contain a date or partial date to indicate the date of deprecation, e.g.

    /**
	 * @deprecated 2016-04
	 * @see OtherClass::excitingfunc
	 */
	 function boringfunc($foo, $bar = false) {
		 zesk()->deprecated("This call is boring");
		 return OtherClass::excitingfunc($foo, $bar);
	 }
	 
Alternate formats for the `@deprecated` DocComment parameter would include the day (`@deprecated 2016-04-01`).

You should run your development systems to support *at a minimum* logging of deprecated functions to ensure their prompt removal from your code.

You can do this by calling one of the following:

	zesk()->set_deprecated(zesk\Kernel::deprecated_log);
	zesk()->set_deprecated(zesk\Kernel::deprecated_exception);
	zesk()->set_deprecated(zesk\Kernel::deprecated_backtrace);
	
### `zesk\Kernel::deprecated_log`

Deprecated functions are logged in the log file. If you want to separate out deprecated calls into their own log file, and are using the `Module_Logger_File` module, set up your configuration like so:

	Module_Logger_File::files::main::name="log/{YYYY}-{MM}-{DD}-main.log"
	Module_Logger_File::files::main::linkname="main.log"
	Module_Logger_File::files::main::exclude_patterns=["/DEPRECATED/i"]
	Module_Logger_File::files::error::name="log/{YYYY}-{MM}-{DD}-error.log"
	Module_Logger_File::files::error::linkname="error.log"
	Module_Logger_File::files::error::level=["error","warning","critical","emergency"]
	Module_Logger_File::files::deprecated::name="log/deprecated.log"
	Module_Logger_File::files::deprecated::include_patterns=["/DEPRECATED/i"]

It will centrally log your deprecated calls in the `deprecated.log` so you can remove them from your code before they are removed from the zesk code.

### `zesk\Kernel::deprecated_exception`

Throw a `zesk\Exception_Deprecated` whenever a deprecated function is called. **Use only during development.** You may miss some issues when exceptions are caught and/or ignored. Useful if you are calling 3rd-party libraries which may use deprecated functionality and you need to know.

### `zesk\Kernel::deprecated_backtrace`

Halt execution and output a `backtrace()` call. The nuclear bomb approach to finding deprecated calls. **Use only during development.**

## How configuration options are deprecated

The configuration object supports option configuration for paths within an application's configuration. To use this within your own code:

	app()->configuration->deprecated($old_path, $new_path);
	
e.g.

	app()->configuration->deprecated("lang::auto", "zesk\Locale::auto");
	
If the old path set in the configuration object, `zesk()->deprecated()` is called, and the code copies value from the old path to the new path, as long as the new path does not have a value as well. 

If the new path has a value, nothing is copied, but the deprecated call is triggered.

## How classes are deprecated or renamed

Classes are deprecated or renamed in a two-phase process unless the change is isolated within the Zesk codebase only (e.g. no external dependencies or usage).

For this example, we'll use the example of the original class called `lang` was renamed to `zesk\Locale` in the `zesk` namespace.

### Phase 1: Extend existing class

Add the new class as a subclass of the old class. If we are adding functions, they should be added only to the new class:

Old class:

	/**
	 * @deprecated 2016-12
	 */
	class lang extends Hookable {
		static $debug = false;
		public static function foo() ...
		public function bar() ...
		public function dee() ...
	}
	
New class:

	namespace zesk;
	class Locale extends \lang {}
	
Note that:

	\lang instanceof \zesk\Locale === false
	\zesk\Locale instanceof lang === true
	
For classes which have instances associated with them.

Note that we only do a DocComment deprecated for the old class to start; adding the deprecated callback would cause the new class to trigger the callback, which we do not want.

### Phase 2: Swap class inheritance

We swap the "highest-level" class with the new class so that functionality remains working, but the old class can be removed more easily as it's now "below" the new class:

Old class:
    
	zesk()->deprecated();
	/**
	 * @deprecated 2016-12
	 */
	class lang extends zesk\Locale {}
	
New class:

	namespace zesk;
	class Locale extends Hookable {
		static $debug = false;
		public static function foo() ...
		public function bar() ...
		public function dee() ...
	}
	
Note that:

	\zesk\Locale instanceof lang === false
	\lang instanceof \zesk\Locale === true
	
For classes which have instances associated with them.

And yes, including the "old" class via the autoloader will invoke the deprecated callback; so this will encourage all application to remove and rename the offending class.

## Deprecated Tools

Within the zesk repository we attempt to make migrating from version to version as easy as possible. So we wrote a tool using `zesk cannon` which allows you to modify your source code automatically.

It should go without saying that you shouldn't run this tool on your source code **unless you do it on a copy which you can recover**. That is - make sure you are using source control, or at least back up your code base before you run `cannon` on it.

The upgrade scripts are found in `bin/deprecated/*version*.sh` where the version is the version you wish to upgrade from. Some times the tool will output things you need to check manually, but they mostly will modify your code to support name changes.

If you're currently on version 1.2, you can run all scripts up to and including version 1.2.

